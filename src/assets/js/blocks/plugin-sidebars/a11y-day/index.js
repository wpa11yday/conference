/**
 * Registering a basic plugin with Gutenberg.
 * Adds functionality to Gutenberg by creating a sidebar plugin
 */

// Import dependencies.
import A11yDaySidebar from './components/sidebar';

const __ = wp.i18n.__;
const registerPlugin = wp.plugins.registerPlugin;
const PluginSidebar = wp.editPost.PluginSidebar
const PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;

// Set a unique name for the sidebar.
const sidebarName = 'a11y-day-sidebar';

// Add a label for the sidebar.
const sidebarLabel = __('WP Accessibility Day', 'wpa-conference');

// Render function.
const render = () => {
	return (
		<>
			<PluginSidebarMoreMenuItem target={sidebarName} icon="admin-settings">
				{sidebarLabel}
			</PluginSidebarMoreMenuItem>
			<PluginSidebar name={sidebarName} title={sidebarLabel}>
				<A11yDaySidebar />
			</PluginSidebar>
		</>
	);
};

// Register the plugin for use in Gutenberg.
registerPlugin(sidebarName, {
	icon: 'admin-settings',
	render,
});
