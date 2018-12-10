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

/**
 * Functionality to label the embed-pagebreaks in the redactor editor on article pages
 * - check when new matrix block is added
 * - listen for changes in the redactor editor 
 * - update the labels of the embed-pagebreaks
 */
var SetEmbeds = {
	init: function() {
		this.$matrixContainer = $('#fields-embeds-field .matrix-field');
		this.$richtextContent = $('#fields-body-field .redactor-box .redactor-in');

        this.$editor = $R('#fields-body');

		// if article page
		if(this.$matrixContainer.length && this.$richtextContent.length) {
			this.initEmbeds();
			window.addEventListener('custom-redactor-events', this.checkEmbeds.bind(this), false);
		}
	},

	initEmbeds: function() {
		this.getPageBreaks();

		// matrix DOM elements
		this.$addBlockBtnContainer = this.$matrixContainer.children('.buttons');
		this.$addBlockBtnGroup = this.$addBlockBtnContainer.children('.btngroup');
		this.$addBlockBtnGroupBtns = this.$addBlockBtnGroup.children('.btn');
		this.$addBlockMenuBtn = this.$addBlockBtnContainer.children('.menubtn');
		this.$blockContainer = this.$matrixContainer.children('.blocks');

		// instances of Garnish UI Lib
		this.matrixInstance = this.$matrixContainer.data('matrix');
		this.matrixMenuBtnInstance = this.$addBlockMenuBtn.data('menubtn');

		this.initEventsListeners();
		this.getBlockTypes();
	},

	getMatrixBlocks: function() {
		// matrixblock DOM elements
		this.$blocks = this.$blockContainer.children();
		this.$blockOptionBtns =  this.$blocks.find('> .actions > .settings');

		// instances of Garnish UI Lib
		this.blocksInstances = this.$blocks.map(function(index, block) {return $(block).data('block')} );
		this.blockOptionsInstances = this.$blockOptionBtns.map(function(index, blockOptionsBtn) {return $(blockOptionsBtn).data('menubtn')} );
	},

	initEventsListeners: function() {	
		// when a matrix block is sorted
		this.matrixInstance.blockSort.on('sortChange', function() {
		    this.getBlockTypes();
		}.bind(this));

		// when a inline version of add-matrixblock-button clicked
		this.$addBlockBtnGroupBtns.on('click', function(ev) {
			this.getBlockTypes();
		}.bind(this));

		// when a select (drop-down) version of add-matrixblock-button clicked
		this.matrixMenuBtnInstance.on('optionSelect', function(ev) {
			this.getBlockTypes();
		}.bind(this));

		this.initBlockOptionsEventListener();
	},

	initBlockOptionsEventListener: function() {
		this.getMatrixBlocks();

		// attach inital event listeners when matrixblock option is selected
		this.blockOptionsInstances.map(function(index, optionsInstance) {
			// overwrite MatrixInput.js function for custom callback
			optionsInstance.menu.settings.onOptionSelect = $.proxy(this, 'blockOptionSelected', index);
		}.bind(this));
	},

	blockOptionSelected: function(index, selectedElement) {
		// trigger original MatrixInput.js callback e.g. to add matrixblock
		var blockInstance = this.blocksInstances[index];
		blockInstance.onMenuOptionSelect(selectedElement);

		// update Emebds
		this.getBlockTypes();

		// add new event listeners for matrix blocks
		setTimeout(function() {
			this.initBlockOptionsEventListener();
		}.bind(this), 400);
	},

	getBlockTypes: function() {
		// TODO check when block added instead of timeout
		setTimeout(function() {
			this.$blocks = this.$blockContainer.children();
			this.blocksInstances = this.$blocks.map(function(index, block) {return $(block).data('block')} );
			this.blockTypes = this.blocksInstances.map(function(index, instance) {return instance.$container.data('type')} );

			this.updateEmbeds();

			// set index to matrixblock
			this.$blocks.map(function(index, block) {
				var $blockHeadline = $(block).find('.titlebar .blocktype');
				$blockHeadline.find('.embed-no').remove();
				$blockHeadline.prepend('<span class="embed-no">'+(index+1)+' </span>');
			})
		}.bind(this), 400);
	},

	getPageBreaks: function() {
		this.$pageBreaks = this.$richtextContent.find('.redactor_pagebreak');
	},

	checkEmbeds: function() {
		this.getPageBreaks();
		if(this.pageBreaksLength !== this.$pageBreaks.length) this.updateEmbeds();
	},

	updateEmbeds: function() {
		// console.log('updateEmbeds');
		this.$pageBreaks.map(function(index, pageBreak) { 
			// get embed type or fallback
			var embedType;
			if(this.blockTypes[index]) {
				embedType = (index + 1) + ' ' + this.capitalizeFirstLetter(this.blockTypes[index]);
			} else {
				embedType = 'Not enough embeds';
			};

			$(pageBreak).eq(0).attr('data-embed', embedType);
		}.bind(this));

		this.pageBreaksLength = this.$pageBreaks.length;
	},

	capitalizeFirstLetter: function(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
	}
}

$(document).ready(function() {

	// TODO check when code ready instead of timeout
	// check if redactor is defined
	if (typeof $R !== 'undefined') {
		setTimeout(function() {
			SetEmbeds.init();

            // redactor character limit for infobox
            if (/entries\/infoBox\/.+/.test(Craft.path)) {
            	var limiter = $R('#fields-infoboxBody').plugin.limiter;
                limiter.opts.limiter = 550;
                limiter.start();
            }
		}, 500);

		// TODO find a better way than timeout here too...
        setTimeout(function() {
            // reset craft content changed javascript confirm popup
            Craft.cp.initConfirmUnloadForms();
        }, 1500);
	}
});


})(jQuery);