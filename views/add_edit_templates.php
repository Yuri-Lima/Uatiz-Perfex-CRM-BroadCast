<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                     <div class="_buttons">
                     	<?php if (has_permission('custom_email_and_sms_notifications', '', 'create') || has_permission('custom_email_and_sms_notifications', '', 'edit')) { ?>
                        	<a href="#" data-toggle="modal" data-target="#add_edit_template" class="btn btn-info mbot30"><?php echo _l('add_edit_templates'); ?></a>
				   		<?php } ?>
                    </div>
                    <div class="clearfix"></div>
                    <hr class="hr-panel-heading" />
                    <div class="clearfix"></div>
                    <?php render_datatable(array(
                        _l('template_name'),
                        _l('options'),
                        ),'template'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('custom_email_and_sms_notifications/template_modal'); ?>
<?php init_tail(); ?>
<script>
   $(function(){
        initDataTable('.table-template', window.location.href, [1], [1]);
   });
</script>
</body>
</html>
