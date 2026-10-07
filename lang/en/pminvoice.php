<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Language Lines
    |--------------------------------------------------------------------------
    |
    | The following menu lines are used for all invoices that we need to display
    | to the user. You are free to modify these language lines according to
    | your application's requirements.
    |
    */
    'billings' => [
        'navLabel' => 'Billing',
        'modelLabel' => 'Billing',
        'pluralModelLabel' => 'Billings',
        'invoice_type_id' => 'Invoice Type',
        'billing_number' => 'Billing Number',
        'billing_date' => 'Billing Date',
        'billing_date_from' => 'Billing Date From',
        'billing_date_until' => 'Billing Date Until',
        'due_date' => 'Due Date',
        'due_date_from' => 'Due Date From',
        'due_date_until' => 'Due Date Until',
        'issued_by' => 'Issued By',
        'issue_to_id' => 'Issue To',
        'issue_to_name' => 'Name',
        'service_period' => 'Service Period',
        'service_duration_from' => 'Start From',
        'service_duration_until' => 'Ends At',
        'unit' => 'Unit(s)',
        'rate_per_unit' => 'Rate',
        'total_amount' => 'Total',
        'penalty_amount' => 'Penalty',
        'total_vat_amount' => 'VAT',
        'discount_amount' => 'Discount',
        'grand_total' => 'Total',
        'status' => 'Status',
        'notes' => 'Notes',
        'all' => 'All',
        'is_issued' => 'Issued',
        'issued' => 'Issued',
        'not_issued' => 'No Issued',
        'issued_at' => 'Issued At',
        'previous_reading' => 'Previous Reading',
        'current_reading' => 'Current Reading',
    ],

    'invoices' => [
        'subject' => 'Subject',
        'navLabel' => 'Invoice',
        'modelLabel' => 'Invoice',
        'pluralModelLabel' => 'Invoices',
        'invoice_number' => 'Invoice Number',
        'invoice_date' => 'Invoice Date',
        'invoice_date_from' => 'Invoice Date From',
        'invoice_date_until' => 'Invoice Date Until',
        'due_date' => 'Due Date',
        'due_date_from' => 'Due Date From',
        'due_date_until' => 'Due Date Until',
        'issued_by' => 'Issued By',
        'issue_to_id' => 'Issue To',
        'issue_to_name' => 'Name',
        'total_amount' => 'Total',
        'penalty_amount' => 'Penalty',
        'total_vat_amount' => 'VAT',
        'discount_amount' => 'Discount',
        'grand_total' => 'Grand Total',
        'status' => 'Status',
        'remarks' => 'Remarks',
        'internal_note' => 'Internal Note',
        'linked_billings' => 'Linked Billings',
        'additional_information' => 'Additional Information',
        'outstanding_invoices' => 'Outstanding Invoices',
    ],

    'credit_notes' => [
        'navLabel' => 'Credit Note',
        'modelLabel' => 'Credit Note',
        'pluralModelLabel' => 'Credit Notes',
        'credit_note_number' => 'Credit Note Number',
        'issued_by' => 'Issued By',
        'issued_at' => 'Issued At',
        'reason' => 'Reason',
        'invoice_total' => 'Invoice Total',
        'credit_note_total' => 'Credit Note Total',
        'type' => 'Type',
        'status' => 'Status',
        'credit_note_details' => 'Credit Note Details',
    ],

    'credit_note_details' => [
        'description' => 'Description',
        'amount' => 'Amount',
    ],

    'charge_by' => 'Charge By',
    'unit_maintenance' => 'Unit Maintenance',
    'unit_maintenance_summaries' => 'Unit Maintenance Summaries',

];
