/**
 * CheckboxControl that updates a UserMeta value.
 */

const CheckboxControl = wp.components.CheckboxControl;
const Component = wp.element.Component;

export class UserMetaCheckboxControl extends Component {
	constructor(props) {
		super(props);

		this.state = {
			checked: '',
		};

		this.setUserMetaValue = this.setUserMetaValue.bind(this);
		this.changeChecked = this.changeChecked.bind(this);
	}

	componentDidMount() {
		wp.apiFetch({
			path: '/wp/v2/users/me',
			method: 'GET',
		}).then((response) => {
			const UserMetaValue = response.meta[this.props.metaKey] || false;
			this.setState({ checked: UserMetaValue });
			this.props.onChange(UserMetaValue);
		});
	}

	setUserMetaValue(UserMetaValue) {
		wp.apiFetch({
			path: '/wp/v2/users/me',
			method: 'POST',
			data: {
				meta: {
					[this.props.metaKey]: UserMetaValue,
				},
			},
		});
	}

	changeChecked(checked) {
		this.setState({ checked });
		this.setUserMetaValue(checked);
		this.props.onChange(checked);
	}

	render() {
		const heading = this.props.heading || '';
		const label = this.props.label || '';
		const help = this.props.help || '';
		const className = this.props.className || '';

		return (
			<CheckboxControl
				heading={heading}
				label={label}
				help={help}
				checked={this.state.checked}
				onChange={this.changeChecked}
				className={className}
			/>
		);
	}
}

export default UserMetaCheckboxControl;
