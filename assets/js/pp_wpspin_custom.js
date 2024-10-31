var $ = jQuery.noConflict()

$(document).ready(function () {
  // find .page-title-action
  var page_title_action = $(".post-type-page .page-title-action");
  page_title_action.after('<a href="#TB_inline?&width=600&height=550&inlineId=pp_wpspin_import" class="page-title-action thickbox pp_wpspin_import">Import</a>');
  var post_title_action = $(".post-type-post .page-title-action");
  post_title_action.after('<a href="#TB_inline?&width=600&height=550&inlineId=pp_wpspin_import" class="page-title-action thickbox pp_wpspin_import">Import</a>');
  //form#pp_wpspin_import_form
  $(document).on("submit", "form#pp_wpspin_import_form", function (e) {
    e.preventDefault();
    var form = $(this);
    var data = new FormData(form[0]);
    data.append("action", "pp_wpspin_import_json");
    $.ajax({
      url: pp_wpspin_ajax.ajax_url,
      type: "POST",
      data: data,
      contentType: false,
      processData: false,
      success: function (response) {
        if (response.success) {
          alert("Data imported successfully");
          tb_remove();
          location.reload();
        } else {
          alert("Data not imported");
        }
      },
      error: function (response) {
        alert("Data not imported");
        console.log(response);
      },
    });
  });
  $(document).on("click", "#pp_wpspin_export", function () {
    var that = $(this)

    var post_id = that.attr("data-id")

    $.ajax({
      beforeSend: function () {},

      url: pp_wpspin_ajax.ajax_url,

      data: { action: "pp_wpspin_export_json", post_id: post_id },

      type: "POST",

      success: function (response) {
        /*Ajax not able to download direct file. So create an empty element that hold the data*/

        var hid_html =
          '<a href="javascript:void(0)" id="pp_wpspin_dlbtn" style="display: none;"><button type="button" id="pp_wpspin_hiddden_download">Download</button></a>'

        //remove old element

        $("#pp_wpspin_hiddden_download").remove()

        //Insert new empty element

        that.parent(".pp_wpspin_download_box_wrap").append(hid_html)

        //store json response in empty element

        var dlbtn = document.getElementById("pp_wpspin_dlbtn")

        var fileToSave = new Blob([response], {
          type: "application/json",
        })

        dlbtn.href = URL.createObjectURL(fileToSave)

        //create a downloadable file name

        dlbtn.download = post_id + "_export.json"

        //Auto click to download file

        $("#pp_wpspin_hiddden_download").click()
      },

      complete: function () {},

      error: function (status, error) {
        var errors = status.responseText

        console.log(errors)
      },
    })
  })
})
