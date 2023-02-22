(function (wp) {
  const registerBlockType = wp.blocks.registerBlockType;
  const InspectorControls = wp.editor.InspectorControls;
  const SelectControl = wp.components.SelectControl;
  const CheckboxControl = wp.components.CheckboxControl;
  const el = wp.element.createElement;
  const ServerSideRender = wp.components.ServerSideRender;
  const DatePicker = wp.components.DateTimePicker;

  /**
   * Get the formatted date.
   *
   * @param {String} date The date string.
   * @returns {String} Formatted date string.
   */
  const wpcs_dateFormatted = (date) => {
    if (date) {
      date = new Date(date);
    } else {
      date = new Date();
    }
    const dd = String(date.getDate()).padStart(2, "0");
    const mm = String(date.getMonth() + 1).padStart(2, "0"); //January is 0!
    const yyyy = date.getFullYear();

    return yyyy + "-" + mm + "-" + dd;
  };

  const trackTermsArray = [];
  wp.apiFetch({ path: "/wp/v2/session_track" }).then((posts) => {
    posts.forEach((val) => {
      trackTermsArray.push({ id: val.id, name: val.name, slug: val.slug });
    });
  });

  registerBlockType("wpcs/schedule-block", {
    title: "Conference Schedule",
    icon: "schedule",
    category: "common",
    supports: {
      align: ["wide", "full"],
    },
    attributes: {
      date: { type: "string", default: wpcs_dateFormatted(null) },
      color_scheme: { type: "string", default: "light" },
      layout: { type: "string", default: "table" },
      row_height: { type: "string", default: "match" },
      session_link: { type: "string", default: "permalink" },
      tracks: { type: "string", default: null },
    },

    edit: function (props) {
      const setAttributes = props.setAttributes;

      const date = props.attributes.date;
      const color_scheme = props.attributes.color_scheme;
      const layout = props.attributes.layout;
      const row_height = props.attributes.row_height;
      const session_link = props.attributes.session_link;
      const tracks = props.attributes.tracks;

      let tracksArray = [];
      if (tracks != null) {
        tracksArray = tracks.split(",");
      }

      const trackCheckboxes = [];
      for (let i = 0; i < trackTermsArray.length; i++) {
        let heading = null;
        if (i == 0) {
          heading = "Tracks";
        }

        trackCheckboxes.push(
          el(CheckboxControl, {
            key: trackTermsArray[i].slug,
            label: trackTermsArray[i].name,
            name: "tracks[]",
            value: trackTermsArray[i].slug,
            checked: tracksArray.includes(trackTermsArray[i].slug),
            heading: heading,
            onChange: function () {
              const track = event.target.value;
              const index = tracksArray.indexOf(track);
              if (index > -1) {
                tracksArray.splice(index, 1);
              } else {
                tracksArray.push(track);
              }
              setAttributes({ tracks: tracksArray.join() });
            },
          })
        );
      }

      return [
        el(ServerSideRender, {
          block: "wpcs/schedule-block",
          attributes: props.attributes,
        }),
        el(
          InspectorControls,
          {},
          el(DatePicker, {
            currentDate: date,
            locale: "en",
            onChange: function (value) {
              setAttributes({ date: wpcs_dateFormatted(value) });
            },
            selected: date,
          }),
          el(SelectControl, {
            label: "Color Scheme",
            value: color_scheme,
            options: [
              { value: "light", label: "Light" },
              { value: "dark", label: "Dark" },
            ],
            onChange: function (value) {
              setAttributes({ color_scheme: value });
            },
          }),
          el(SelectControl, {
            label: "Layout",
            value: layout,
            options: [
              { value: "table", label: "Table" },
              { value: "grid", label: "Grid" },
            ],
            onChange: function (value) {
              setAttributes({ layout: value });
            },
          }),
          el(SelectControl, {
            label: "Row height",
            value: row_height,
            options: [
              { value: "match", label: "Match" },
              { value: "auto", label: "Auto" },
            ],
            onChange: function (value) {
              setAttributes({ row_height: value });
            },
          }),
          el(SelectControl, {
            label: "Session Link",
            value: session_link,
            options: [
              { value: "permalink", label: "Permalink" },
              { value: "anchor", label: "Anchor" },
              { value: "none", label: "None" },
            ],
            onChange: function (value) {
              setAttributes({ session_link: value });
            },
          }),
          trackCheckboxes
        ),
      ];
    },

    save: function () {
      return null;
    },
  });
})(window.wp);
