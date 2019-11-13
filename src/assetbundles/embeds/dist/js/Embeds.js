/**
 * Embeds plugin for Craft CMS
 *
 * Embeds JS
 *
 * @author    Fork Unstable Media GmbH
 * @copyright Copyright (c) 2018 Fork Unstable Media GmbH
 * @link      http://fork.de
 * @package   Embeds
 * @since     1.0.0
 */



(function($){
	var embedElements = [];
	var embedsName = 'embeds';
	var embedsCopyName = 'embedsCopy';

	Craft.initEmbeds = function(embedsNameSetting, embedsCopyNameSetting) {
		embedsName = embedsNameSetting;
		embedsCopyName = embedsCopyNameSetting;
		// for single editor and embeds
		if ($('#fields-'+embedsName+'-field').length && $('#fields-'+embedsCopyName+'-field').length) {
			var $matrixField = $('#fields-'+embedsName+'-field .matrix-field');
			var $redactor = $('#fields-'+embedsCopyName+'-field .redactor-box .redactor-in');

			// init single Embed
			new Embed($matrixField, $redactor);
		}

		if ($('.superTableContainer.matrixLayout')) {
			// init editor/embeds for all superTableRows
			getSupertableEmbeds();

			// event listener for new superTableRow
			$('.superTableContainer.matrixLayout .superTableAddRow').on('click', getSupertableEmbeds);
		}
	}

	function getSupertableEmbeds() {
		// get all children of supertable with editor and embeds
		var superTableBlocks = $('.superTableMatrix.matrixblock');
		for (var i = 0; i < superTableBlocks.length; i++) {
			var editor = $(superTableBlocks[i])
				.find(' > .fields > .field')
				.filter(function(index) {
					return (
						$(this)
							.attr('id')
							.indexOf('fields-'+embedsCopyName+'-field') >= 1
					);
				})
				.find('.redactor-box .redactor-in');
			var matrix = $(superTableBlocks[i])
				.find(' > .fields > .field')
				.filter(function(index) {
					return (
						$(this)
							.attr('id')
							.indexOf('fields-'+embedsName+'-field') >= 1
					);
				})
				.find('.matrix-field');
			if (editor.length && matrix.length) {
				embedElements.push({
					$block: $(superTableBlocks[i]),
					$matrixField: $(matrix),
					$redactor: $(editor)
				});
			}
		}

		// init Embed that have not been initialized
		for (var i = 0; i < embedElements.length; i++) {
			if (!$(embedElements[i].$block).data('embed-plugin-initialized')) {
				new Embed(embedElements[i].$matrixField, embedElements[i].$redactor);
				$(embedElements[i].$block).data('embed-plugin-initialized', true);
			}
		}
	}

	function Embed($matrixField, $redactor) {
		this.$matrixField = $matrixField;
		this.$redactor = $redactor;

		this.init = function() {
			this.getPageBreaks();

			// matrix DOM elements
			var $addBlockBtnContainer = this.$matrixField.find('.buttons');
			this.$addBlockBtnGroupBtns = $addBlockBtnContainer.find('.btngroup .btn');
			this.$blockContainer = this.$matrixField.children('.blocks');

			// instances of Garnish UI matrix block
			this.matrixInstance = this.$matrixField.data('matrix');

			// instances of Garnish UI menubtn (https://github.com/pixelandtonic/garnishjs/blob/3e57331081c277eeac9a022feeadac5da3f4a2f9/src/MenuBtn.js)
			this.matrixMenuBtnInstance = $addBlockBtnContainer.find('.menubtn').data('menubtn');

			this.initEventsListener();
			this.updateBlocks();

			// redactor event listeners
			window.addEventListener('custom-redactor-events', this.checkEmbeds.bind(this), false);
		};

		this.initEventsListener = function() {
			// when a matrix block is sorted
			this.matrixInstance.blockSort.on(
				'sortChange /',
				function() {
					this.updateBlocks();
				}.bind(this)
			);

			// when a inline version of add-matrixblock-button clicked
			this.$addBlockBtnGroupBtns.on(
				'click',
				function(ev) {
					this.updateBlocks();
				}.bind(this)
			);

			// when a select (drop-down) version of add-matrixblock-button clicked
			this.matrixMenuBtnInstance.on(
				'optionSelect',
				function(ev) {
					this.updateBlocks();
				}.bind(this)
			);
		};

		this.blockOptionSelected = function(index, selectedElement) {
			// trigger original MatrixInput.js callback e.g. to add matrixblock
			var blockInstance = this.blocksInstances[index];
			blockInstance.onMenuOptionSelect(selectedElement);

			// update Blocks
			this.updateBlocks();
		};

		this.initBlockOptionsEventListener = function() {
			// attach inital event listeners when matrixblock option is selected
			this.blockOptionsInstances.map(
				function(index, optionsInstance) {
					// overwrite MatrixInput.js function for custom callback
					optionsInstance.menu.settings.onOptionSelect = $.proxy(this, 'blockOptionSelected', index);
				}.bind(this)
			);
		};

		this.getBlocks = function() {
			// matrixblock DOM elements
			this.$blocks = this.$blockContainer.children();
			this.$activeBlocks = this.$blocks.not('.disabled');
			this.$blockOptionBtns = this.$blocks.find('> .actions > .settings');

			// instances of Garnish UI Lib
			this.blocksInstances = this.$blocks.map(function(index, block) {
				return $(block).data('block');
			});
			this.activeBlocksInstances = this.$activeBlocks.map(function(index, block) {
				return $(block).data('block');
			});
			this.blockOptionsInstances = this.$blockOptionBtns.map(function(index, blockOptionsBtn) {
				return $(blockOptionsBtn).data('menubtn');
			});
		};

		this.updateBlocks = function() {
			// TODO check when block added instead of timeout
			setTimeout(
				function() {
					this.getBlocks();

					// add new event listeners for matrix blocks
					this.initBlockOptionsEventListener();

					// set index to matrixblock
					this.$blocks.map(function(index, block) {
						var $blockHeadline = $(block).find('.titlebar .blocktype');
						$blockHeadline.find('.embed-no').remove();
						$blockHeadline.prepend('<span class="embed-no">' + (index + 1) + ' </span>');
					});

					this.updateEmbeds();
				}.bind(this),
				400
			);
		};

		this.getPageBreaks = function() {
			this.$pageBreaks = this.$redactor.find('.redactor_pagebreak');
		};

		this.checkEmbeds = function() {
			this.getPageBreaks();
			if (this.pageBreaksLength !== this.$pageBreaks.length) this.updateEmbeds();
		};

		this.updateEmbeds = function() {
			this.$pageBreaks.map(
				function(index, pageBreak) {
					// get embed type or fallback
					var embedType;
					if (this.activeBlocksInstances[index]) {
						var embedType = this.capitalizeFirstLetter(this.activeBlocksInstances[index].$container.data('type'));
						var embedID = this.activeBlocksInstances[index].$container.find('.embed-no').html();
						embedType = embedID + embedType;
					} else {
						embedType = 'Not enough embeds';
					}

					$(pageBreak)
						.eq(0)
						.attr('data-embed', embedType);
				}.bind(this)
			);

			this.pageBreaksLength = this.$pageBreaks.length;
		};

		this.capitalizeFirstLetter = function(string) {
			return string.charAt(0).toUpperCase() + string.slice(1);
		};

		this.init();
	}

})(jQuery);