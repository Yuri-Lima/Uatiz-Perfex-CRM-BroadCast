<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="add_edit_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button group="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('edit_templates'); ?></span>
                    <span class="add-title"><?php echo _l('add_templates'); ?></span>
                </h4>
            </div>
            <?php echo form_open('custom_email_and_sms_notifications/template/save',array('id'=>'custom-template-modal')); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo render_input('template_name','template_name'); ?>
                        <?php echo render_textarea('template_content','template_content', '', [], [], '', 'tinymce'); ?>
                        <?php echo form_hidden('id'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button group="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button group="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load',function(){
       appValidateForm($('#custom-template-modal'), {
        template_name: 'required',
        template_content: 'required',
    }, custom_template_modal_submit);

       $('#add_edit_template').on('show.bs.modal', function(e) {

         init_editor();

        var invoker = $(e.relatedTarget);
        var group_id = $(invoker).data('id');
        $('#add_edit_template .add-title').removeClass('hide');
        $('#add_edit_template .edit-title').addClass('hide');
        $('#add_edit_template input[name="id"]').val('');
        $('#add_edit_template input[name="template_name"]').val('');
        tinyMCE.activeEditor.setContent('');
        // is from the edit button
        if (typeof(group_id) !== 'undefined') {
            $('#add_edit_template .add-title').addClass('hide');
            $('#add_edit_template .edit-title').removeClass('hide');
            requestGetJSON('custom_email_and_sms_notifications/template/get_item_by_id/' + group_id).done(function (response) {
               $('#add_edit_template input[name="template_content"]').val(response.template_content);
               $('#add_edit_template input[name="id"]').val(group_id);
               $('#add_edit_template input[name="template_name"]').val(response.template_name);
               tinyMCE.activeEditor.setContent(response.template_content);
            });
        }
    });
   });
    function custom_template_modal_submit(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                if($.fn.DataTable.isDataTable('.table-template')){
                    $('.table-template').DataTable().ajax.reload();
                }
                if($('body').hasClass('dynamic-create-groups') && typeof(response.id) != 'undefined') {
                    var groups = $('select[name="groups_in[]"]');
                    groups.prepend('<option value="'+response.id+'">'+response.name+'</option>');
                    groups.selectpicker('refresh');
                }
                alert_float('success', response.message);
            }
            $('#add_edit_template').modal('hide');
        });
        return false;
    }

</script>
