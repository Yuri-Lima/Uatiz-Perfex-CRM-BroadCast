<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = ['template_name'];

$sIndexColumn = 'id';
$sTable       = db_prefix().'custom_templates';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0 ; $i < count($aColumns) ; $i++) {
        $_data = '<a href="#" data-toggle="modal" data-target="#add_edit_template" data-id="' . $aRow['id'] . '">' . $aRow[$aColumns[$i]] . '</a>';

        $row[] = $_data;
    }
    $options = icon_btn('#', 'pencil-square-o', 'btn-default', ['data-toggle' => 'modal', 'data-target' => '#add_edit_template', 'data-id' => $aRow['id']]);
    $row[]   = $options .= icon_btn('custom_email_and_sms_notifications/template/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');

    $output['aaData'][] = $row;
}
