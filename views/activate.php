<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-6">
            <div class="panel_s">
               <div class="panel-body">
			   <h4>Module activation</h4>
			   <hr class="hr-panel-heading">
			   Please activate your product, using your license purchase key (<a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">where can I find my purchase key?</a>)
			   <br><br>
			   <?php echo form_open($submit_url, ['autocomplete'=>'off', 'id'=>'verify-form']); ?>
                        <?php echo form_hidden('original_url', $original_url); ?> 
                  		<?php echo form_hidden('module_name', $module_name); ?> 
								<?php echo render_input('purchase_key', 'purchase_key', '', 'text', ['required'=>true]); ?>
                  		<button id="submit" type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
                  	<?php echo form_close(); ?>
               </div>
            </div>
         </div>
         <div class="col-md-6">
		 </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
   appValidateForm($('#verify-form'), {
        purchase_key: 'required'
    }, manage_verify_form);

   function manage_verify_form(form) {
      var data = $(form).serialize();
      var url = form.action;
      $("#submit").prop('disabled', true).prepend('<i class="fa fa-spinner fa-pulse"></i> ');
      $.post(url, data).done(function(response) {
         var response = $.parseJSON(response);
         if(!response.status){
            alert_float("danger",response.message);
         }
         if(response.status){
            alert_float("success","Activating....");
            window.location.href = response.original_url;
         }
         $("#submit").prop('disabled', false).find('i').remove();
      });
   }
</script>