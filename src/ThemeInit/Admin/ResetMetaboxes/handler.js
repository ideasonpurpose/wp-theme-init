/**
 * global var provided by wp_localize_script()
 * global iop_metabox_config
 */
const handler = (e) => {
  const className = Array.from(e.target.classList)
    .filter((cn) => cn.indexOf("iop-reset-metabox") > -1)
    .toString();

  const data = {
    _ajax_nonce: iop_metabox_config.nonce,
    action: iop_metabox_config.action,
    class_name: className,
  };

  fetch(iop_metabox_config.url, {
    method: "POST",
    body: new URLSearchParams(data),

    /**
     * Headers aren't needed? Inheriting from URLSearchParams?
     */
    // headers: {
    //   "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
    // },
  })
    .then((response) => response.json())
    .then((data) => {
      e.target.disabled = true;
      jQuery(data.selector)
        .parent()
        .prepend(
          `<div class="notice notice-success inline"><p>${data.message}</p></div>`
        );
    })
    .catch((error) => {
      console.error("Error:", error);
    });
};

document
  .querySelectorAll("button[class*=iop-reset-metabox]")
  .forEach((el) => el.addEventListener("click", handler));

/**
 * Leftover, fetch() is working and faster...
 */
// jQuery.post(
//   iop_metabox_config.url,
//   {
//     _ajax_nonce: iop_metabox_config.nonce,
//     action: iop_metabox_config.action,
//     title: "hello i am title",
//   },
//   function (result) {
//     console.log({ result, data });
//   }
// );
