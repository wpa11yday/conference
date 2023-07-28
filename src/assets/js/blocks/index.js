const registerPlugin = wp.plugins.registerPlugin;

const postStatusSelect = () => {
  const PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo; // eslint-disable-line no-unused-vars
  const __ = wp.i18n.__;
  const SelectControl = wp.components.SelectControl; // eslint-disable-line no-unused-vars
  const useSelect = wp.data.useSelect;
  const useDispatch = wp.data.useDispatch;

  const status = useSelect((select) => {
    return select("core/editor").getEditedPostAttribute("status");
  }, []);

  const prePubStati = ["auto-draft", "draft", "pending", "approved"];

  if (!prePubStati.includes(status)) {
    return;
  }

  const editPost = useDispatch("core/editor").editPost;
  const savePost = useDispatch("core/editor").savePost;

  return (
    <PluginPostStatusInfo
      name="prefix-post-change"
      title={__("Post Status", "wpa-conference")}
      className="prefix-post-change"
      initialOpen={true}
    >
      <SelectControl
        label={__("Set post status", "wpa-conference")}
        value={status}
        options={[
          { label: __("Draft", "wpa-conference"), value: "draft" },
          { label: __("Pending Review", "wpa-conference"), value: "pending" },
          { label: __("Approved", "wpa-conference"), value: "approved" },
        ]}
        onChange={(status) => {
          editPost({
            status: status,
          });
          savePost();
        }}
      />
    </PluginPostStatusInfo>
  );
};

registerPlugin("wpcs-post-status-select", {
  render: postStatusSelect,
});
