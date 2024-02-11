/**
 * A11y Day plugin sidebar component.
 */

import UserMetaCheckboxControl from './meta/checkbox';

const __ = wp.i18n.__;
const Component = wp.element.Component;
const PanelBody = wp.components.PanelBody;
const PanelRow = wp.components.PanelRow;

export class A11yDaySidebar extends Component {
	constructor() {
		super();

		// Initialize the state.
		this.state = {
			disableFrontEndStyles: false,
		};

		// Bind methods to "this".
		this.setdisableFrontEndStyles = this.setdisableFrontEndStyles.bind(this);
	}

	setdisableFrontEndStyles(disableFrontEndStyles) {
		this.setState({ disableFrontEndStyles });
	}

	render() {
		return (
			<PanelBody
				title={__('Editor display options', 'wpa-conference')}
				initialOpen={true}
			>
				<PanelRow>
					<UserMetaCheckboxControl
						label={__('Disable front end styles?', 'wpa-conference')}
						help={__('After changing this option, refresh to see the style update.', 'wpa-conference')}
						metaKey="disable_front_end_styles"
						onChange={this.setdisableFrontEndStyles}
					/>
				</PanelRow>
			</PanelBody>
		);
	}
}

export default A11yDaySidebar;
