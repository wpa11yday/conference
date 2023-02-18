(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  $(function () {

  });

  $(window).on('load',function () {

  });
  
})(jQuery);

window.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('[role="tab"]');
  const tabList = document.querySelector('[role="tablist"]');

  // Add a click event handler to each tab
  tabs.forEach(tab => {
    tab.addEventListener('click', wpcsp_changeTabs);
  });

  // Enable arrow navigation between tabs in the tab list
  let tabFocus = 0;

  tabList.addEventListener('keydown', e => {
    // Move right
    if (e.keyCode === 39 || e.keyCode === 37) {
      tabs[tabFocus].setAttribute('tabindex', -1);
      if (e.keyCode === 39) {
        tabFocus++;
        // If we're at the end, go to the start
        if (tabFocus >= tabs.length) {
          tabFocus = 0;
        }
        // Move left
      } else if (e.keyCode === 37) {
        tabFocus--;
        // If we're at the start, move to the end
        if (tabFocus < 0) {
          tabFocus = tabs.length - 1;
        }
      }

      tabs[tabFocus].setAttribute('tabindex', 0);
      tabs[tabFocus].focus();
    }
  });
});

function wpcsp_changeTabs(e) {
  const target = e.target;
  const parent = target.parentNode;
  const grandparent = parent.parentNode;

  // Remove all current selected tabs
  parent
    .querySelectorAll('[aria-selected="true"]')
    .forEach(t => t.setAttribute('aria-selected', false));

  // Set this tab as selected
  target.setAttribute('aria-selected', true);

  // Hide all tab panels
  grandparent
    .querySelectorAll('[role="tabpanel"]')
    .forEach(p => p.setAttribute('hidden', true));

  // Show the selected panel
  grandparent.parentNode
    .querySelector(`#${target.getAttribute('aria-controls')}`)
    .removeAttribute('hidden');

  // Set query parameter
  if ('URLSearchParams' in window) {
    var searchParams = new URLSearchParams(window.location.search)
    searchParams.set("wpcs-tab", target.getAttribute('data-id'));
    var newRelativePathQuery = window.location.pathname + '?' + searchParams.toString();
    history.pushState(null, '', newRelativePathQuery);
  }

}