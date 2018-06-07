$(function(){

  var ul = $('#upload ul');

  $('#drop a').click(function(){
    $(this).parent().find('input').click();
  });

  $('#upload').fileupload({
    dataType : 'json',
//    dataType : 'text',
    autoUpload: true,
    dropZone: $('#drop'),

    add: function (e, data) {

var image_info = "";
image_info += "<p id='lat'>lat</p>";
image_info += "<p id='lon'>lon</p>";
image_info += "<p id='time'>time</p>";

var tpl_text = "";
tpl_text += "<li class='working'>";
tpl_text += "<div style='float:left; height:100px; background:magenta; margin:1px'>";
tpl_text += "<input type='text' value='0' data-width='48' data-height='48' data-fgColor='#0080a0' data-readOnly='1' data-bgColor='#404040' />";
tpl_text += "</div>";
tpl_text += "<div id='info' style='float:left; height:100px; background:green; margin:1px'>";
tpl_text += "</div>";
tpl_text += "<div style='float:left; height:100px; background:cyan; margin:1px'>";
tpl_text += "<span id='x'>In progress</span>";
tpl_text += "</div>";
tpl_text += "<br clear='all'>";
tpl_text += "</li>";

content = "<p>" + data.files[0].name + "<p>";
content += "<p>" + formatFileSize(data.files[0].size) + "</p>" + image_info;

      var tpl = $(tpl_text);

      tpl.find('div#info').html(content);

      // Add the HTML to the UL element
      data.context = tpl.appendTo(ul);

      // Initialize the knob plugin
      tpl.find('input').knob();

      // Listen for clicks on the cancel icon
      tpl.find('span#x').click(function(){

        if(tpl.hasClass('working')){
          jqXHR.abort();
        }

        tpl.fadeOut(function(){
          tpl.remove();
        });

      });

      // Automatically upload the file once it is added to the queue
      var jqXHR = data.submit();
    },

    progress: function(e, data){

      // Calculate the completion percentage of the upload
      var progress = parseInt(data.loaded / data.total * 100, 10);

      // Update the hidden input field and trigger a change
      // so that the jQuery knob plugin knows to update the dial
      data.context.find('input').val(progress).change();

      if (progress == 100){
        $('span#x').text("done");
      }
    },

    fail:function(e, data){
      // Something has gone wrong!
      data.context.addClass('error');
      alert('Fail!'+data.toString());
    },

    sent:function(e, data){
       alert('sent done');
    },

    uploaddone:function(e, data){
       alert('done');
    },

    stop:function(e, data){
       alert('stop');
    },

    always: function (e, data) {
      alert(data.result.files[2].error);
      $('#lat').text(data.result.files[2].lat);
      $('#lon').text(data.result.files[2].lon);
      $('#time').text(data.result.files[2].time);
//      $('p').text(data.result.files[2].lat);
//  alert(data.textStatus);
  // data.jqXHR;
    }


  });

  $(document).on('drop dragover', function (e) {
    e.preventDefault();
  });

  function formatFileSize(bytes) {
    if (typeof bytes !== 'number') {
      return '';
    }

    if (bytes >= 1000000000) {
      return (bytes / 1000000000).toFixed(2) + ' GB';
    }

    if (bytes >= 1000000) {
      return (bytes / 1000000).toFixed(2) + ' MB';
    }

    return (bytes / 1000).toFixed(2) + ' KB';
  }

});
