/**
 * BLOCK: Sticky Block
 */

import './style.scss';
import './editor.scss';

const { __ } = wp.i18n;
const { 
    registerBlockType, 
    RichText,          
    source             
} = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, PanelRow, SelectControl, CheckboxControl, RangeControl, FormToggle, ToggleControl, TextControl } = wp.components;
import { InnerBlocks } from '@wordpress/block-editor';
const { withState } = wp.compose;

registerBlockType( 'senff/sticky-block', {
	title: __( 'Sticky' ),
	icon: 'sticky', 
	category: 'layout',
	keywords: [
		__( 'Sticky' ),
		__( 'Senff' ),
	],

    attributes: {
	    topSpace: {
	      type: "integer",
	      default: 0
	    },
        checkForAdmin: {
            type: 'boolean',
            default: true
        },	    
	    minWidth: {
	      type: "integer",
	      default: 0
	    }, 
	    maxWidth: {
	      type: "integer",
	      default: 99999
	    }, 
	    pushUp: {
	      type: "text",
	      default: ""
	    },
	    zee: {
	    	type: "integer",
	    	default: 1
	    }
    },

	edit: function( props ) {
		var topSpace = props.attributes.topSpace
        var checkForAdmin = props.attributes.checkForAdmin
		var minWidth = props.attributes.minWidth
		var maxWidth = props.attributes.maxWidth	
		var pushUp = props.attributes.pushUp
		var zee = props.attributes.zee

        // Keep this for later
        // function onChangeCheckbox ( content ) {
        //     props.setAttributes( { check_bigbox: ! props.attributes.check_bigbox } ) 
        // }

		return [
			<InspectorControls>
				<PanelBody title={ __( 'Sticky Options' ) }>
					<TextControl
			        	label={__("Space between sticky block and top of screen:")}
			        	value={topSpace}
			        	type="number"
			        	onChange={value =>
			        		props.setAttributes({ topSpace: Number.parseInt(value, 10) })
			        	}
			      	/>
				  	<ToggleControl
        				label={__("Move the block down a little bit if there is a toolbar at the top (for logged in users)")}
        				checked={ props.attributes.checkForAdmin }
        				onChange={value =>
        					props.setAttributes( { checkForAdmin: ! props.attributes.checkForAdmin } ) 
        				}
      				/>
      				<TextControl
			        	label={__("Block should not be sticky on screens smaller than:")}
			        	value={minWidth}
			        	type="number"
			        	onChange={value =>
			          		props.setAttributes({ minWidth: Number.parseInt(value, 10) })
			        	}
			      	/>	
			      	<TextControl
			        	label={__("Block should not be sticky on screens wider than:")}
			        	value={maxWidth}
			        	type="number"
			        	onChange={value =>
			          		props.setAttributes({ maxWidth: Number.parseInt(value, 10) })
			        	}
			      	/>	
			      	<TextControl
			        	label={__("Pushup Element:")}
			        	value={pushUp}
			        	type="text"
			        	onChange={value =>
			          		props.setAttributes({ pushUp: value })
			        	}
			        	help="e.g. '#footer', '.widget-bottom', etc."			        	
			      	/>
			      	<RangeControl
			      		label={__("Z-index:")}
			      		value={zee}
			      		min={-100}
        				max={1000}
			      		onChange={value =>
			          		props.setAttributes({ zee: value })
			        	}
			        	help="Only applies once the element is sticky"
			      	/>		            			
				</PanelBody>
			</InspectorControls>
			,
			<div className={ props.className }>
				<InnerBlocks/>
			</div>
		];
	},

	save: function( props ) {

        var topSpace = props.attributes.topSpace;
        var checkForAdmin = props.attributes.checkForAdmin;
        var minScreen = props.attributes.minWidth;
        var maxScreen = props.attributes.maxWidth;        
		var pushUp = props.attributes.pushUp; 
		var zee = props.attributes.zee;
		return (
			<div className={ props.className } data-topSpace={topSpace} data-cfa={checkForAdmin} data-minwidth={minScreen} data-maxwidth={maxScreen} data-pushup={pushUp} data-zindex={zee}>
				<InnerBlocks.Content />
			</div>
		);
	},
} );