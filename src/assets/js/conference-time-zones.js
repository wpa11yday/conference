jQuery(($) => {
  let zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  // Handle Internet Explorer's lack of timezone info.
  if (undefined === zone) {
    zone = "your local time";
  }
  $(".event-time").each(function () {
    const utcTime = $(this).attr("data-time");
    const userTime = new Date(utcTime)
      .toLocaleTimeString()
      .replace(":00 ", " ");
    const userDate = new Date(utcTime).toLocaleDateString();

    $(this).append(
      '<span class="localtime">' +
        userDate +
        " at " +
        userTime +
        " " +
        zone +
        "</span>"
    );
  });
  $("h2.talk-time").each(function () {
    const utcTime = $(this).attr("data-time");
    const userTime = new Date(utcTime)
      .toLocaleTimeString()
      .replace(":00 ", " ");

    $(this)
      .find(".time-wrapper")
      .append('<span class="localtime">' + userTime + " " + zone + "</span>");
  });
});
