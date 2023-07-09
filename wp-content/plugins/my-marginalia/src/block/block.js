/**
 * BLOCK: marginalia
 *
 */

//  Import CSS.
import './style.scss';
import './editor.scss';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { RichText } = wp.editor;
const { Fragment } = wp.element;
const { InnerBlocks, BlockControls } = wp.editor;
const { Component } = wp.element;
const { InspectorControls } = wp.editor;
const { PanelBody, RangeControl, Button, SelectControl, FormatToolbar } = wp.components;

registerBlockType( 'davidfcarr/marginalia', {
	title: ( 'Marginalia' ), // Block title.
	icon: 'admin-comments', 
	category: 'layout',
	keywords: [
		( 'Margin' ),
		( 'Show Notes' ),
		( 'Wrapper' ),
	],
attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'p',
        },
	position: {
		type: 'string',
		default: marginalia.position,
	},
	width: {
		type: 'number',
		default: parseInt(marginalia.width),
	},
},

    edit: function( props ) {	

	const { attributes, className, setAttributes, isSelected } = props;
	const marginstyle = {width: attributes.width + 'em'};
	const innerstyle = {marginLeft: (attributes.width + 2) + 'em'};

	return (
		<Fragment>
		<Inspector { ...props } />
<div className={className} ><div class="warnifnested">Nested inside another Marginalia block</div>
		<div style={marginstyle} class="marginalia">
			<RichText
                tagName="p"
                className="marginaliaNote"
                value={attributes.content}
                onChange={(content) => setAttributes({ content })}
            />
	</div>
	<small>(float:{attributes.position})</small>
		<div className="marginaliaMain" style={innerstyle}>
	<InnerBlocks />
	</div>
	
	</div>
		</Fragment>
		);
    },
    save: function( { attributes, className } ) {
	const marginstyle = {};
	const innerstyle = {};
	if(attributes.position == 'left')
		{
			marginstyle['float'] = 'left;'
			marginstyle['width'] = attributes.width + 'em';
			innerstyle['marginLeft'] = (attributes.width + 2) + 'em';
		}
	else
		{
			marginstyle['float'] = 'right;'
			marginstyle['width'] = attributes.width + 'em';
			innerstyle['marginRight'] = (attributes.width + 2) + 'em';
		}
		return <div className={className}><div style={marginstyle} className="marginalia"><RichText.Content tagName="p" value={ attributes.content } /></div><div style={innerstyle} className="marginaliaMain"><InnerBlocks.Content /></div></div>;
    }
});

class Inspector extends Component {

	render() {
		const { attributes, setAttributes, className } = this.props;
/*		const {
			inlineToolbar = false,
			formattingControls,
			keepPlaceholderOnFocus = false,
		} = this.props;
<BlockControls controls={ [
                        {
                            icon: 'edit',
                            title: __( 'Insert Shortcode' ),
                        },
                    ] } />
	<div style={{border: 'thin solid #000;'}}>
			<RichText
                tagName="p"
                className={className}
                value={attributes.content}
                onChange={(content) => setAttributes({ content })}
            />
</div>

*/
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Width and Placement', 'marginalia' ) } >
					<RangeControl
						value={ attributes.width }
						label={ __( 'Margin Width (em)', 'marginalia' ) }
						onChange={ ( width ) => setAttributes( { width } ) }
						min={ 5 }
						max={ 20 }
						allowReset={ true }
					/>
					<SelectControl
							label={ __( 'Position', 'marginalia' ) }
							value={ attributes.position }
							onChange={ ( position ) => setAttributes( { position } ) }
							options={ [
								{ value: 'left', label: __( 'Left', 'marginalia' ) },
								{ value: 'right', label: __( 'Right', 'marginalia' ) },
							] }
						/>
				</PanelBody>
			</InspectorControls>
		);
	}
}

function setMarginaliaWidth( width ) {
	marginalia.width = width;
	alert('New default width: '+width);
    xhr = new XMLHttpRequest();
	xhr.open('POST', ajaxurl);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(encodeURI('action=set_marginalia_default&width=' + width));
}