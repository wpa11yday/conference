(() => {
  // src/assets/js/blocks/index.js
  var registerPlugin = wp.plugins.registerPlugin;
  var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
  var __ = wp.i18n.__;
  var SelectControl = wp.components.SelectControl;
  var useSelect = wp.data.useSelect;
  var useDispatch = wp.data.useDispatch;
  var postStatusSelect = () => {
    const status = useSelect((select) => {
      return select("core/editor").getEditedPostAttribute("status");
    }, []);
    console.log(status);
    const prePubStati = [
      "auto-draft",
      "draft",
      "pending",
      "approved"
    ];
    if (!prePubStati.includes(status)) {
      return;
    }
    const editPost = useDispatch("core/editor").editPost;
    const savePost = useDispatch("core/editor").savePost;
    return /* @__PURE__ */ React.createElement(
      PluginPostStatusInfo,
      {
        name: "prefix-post-change",
        title: __("Post Status", "wpa-conference"),
        className: "prefix-post-change",
        initialOpen: true
      },
      /* @__PURE__ */ React.createElement(
        SelectControl,
        {
          label: __("Set post status", "wpa-conference"),
          value: status,
          options: [
            { label: __("Draft", "wpa-conference"), value: "draft" },
            { label: __("Pending Review", "wpa-conference"), value: "pending" },
            { label: __("Approved", "wpa-conference"), value: "approved" }
          ],
          onChange: (status2) => {
            editPost({
              status: status2
            });
            savePost();
          }
        }
      )
    );
  };
  registerPlugin("wpcs-post-status-select", {
    render: postStatusSelect
  });
})();
//# sourceMappingURL=index-min.js.map
