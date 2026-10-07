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
        'navLabel' => 'การเรียกเก็บเงิน',
        'modelLabel' => 'การเรียกเก็บเงิน',
        'pluralModelLabel' => 'การเรียกเก็บเงินทั้งหมด',
        'invoice_type_id' => 'ประเภทใบแจ้งหนี้',
        'billing_number' => 'เลขที่บิล',
        'billing_date' => 'วันที่ออกบิล',
        'billing_date_from' => 'วันที่ออกบิล (ตั้งแต่)',
        'billing_date_until' => 'วันที่ออกบิล (จนถึง)',
        'due_date' => 'กำหนดชำระ',
        'due_date_from' => 'กำหนดชำระ (ตั้งแต่)',
        'due_date_until' => 'กำหนดชำระ (จนถึง)',
        'issued_by' => 'ออกโดย',
        'issue_to_id' => 'ผู้ออกให้',
        'issue_to_name' => 'ชื่อ',
        'service_period' => 'ระยะเวลาบริการ',
        'service_duration_from' => 'เริ่มต้นจาก',
        'service_duration_until' => 'สิ้นสุดที่',
        'unit' => 'หน่วย',
        'rate_per_unit' => 'อัตรา',
        'total_amount' => 'รวมทั้งหมด',
        'penalty_amount' => 'ค่าปรับ',
        'total_vat_amount' => 'ภาษีมูลค่าเพิ่ม',
        'discount_amount' => 'ส่วนลด',
        'grand_total' => 'ยอดรวมสุทธิ',
        'status' => 'สถานะ',
        'notes' => 'หมายเหตุ',
        'all' => 'ทั้งหมด',
        'is_issued' => 'ออกแล้ว',
        'issued' => 'ออกแล้ว',
        'not_issued' => 'ยังไม่ออก',
        'issued_at' => 'ออกเมื่อ',
        'previous_reading' => 'การอ่านก่อนหน้า',
        'current_reading' => 'การอ่านปัจจุบัน',
    ],

    'invoices' => [
        'subject' => 'หัวข้อ',
        'navLabel' => 'ใบแจ้งหนี้',
        'modelLabel' => 'ใบแจ้งหนี้',
        'pluralModelLabel' => 'ใบแจ้งหนี้ทั้งหมด',
        'invoice_number' => 'เลขที่ใบแจ้งหนี้',
        'invoice_date' => 'วันที่ใบแจ้งหนี้',
        'invoice_date_from' => 'วันที่ใบแจ้งหนี้ (ตั้งแต่)',
        'invoice_date_until' => 'วันที่ใบแจ้งหนี้ (จนถึง)',
        'due_date' => 'กำหนดชำระ',
        'due_date_from' => 'กำหนดชำระ (ตั้งแต่)',
        'due_date_until' => 'กำหนดชำระ (จนถึง)',
        'issued_by' => 'ออกโดย',
        'issue_to_id' => 'ผู้ออกให้',
        'issue_to_name' => 'ชื่อ',
        'total_amount' => 'รวมทั้งหมด',
        'penalty_amount' => 'ค่าปรับ',
        'total_vat_amount' => 'ภาษีมูลค่าเพิ่ม',
        'discount_amount' => 'ส่วนลด',
        'grand_total' => 'ยอดรวมสุทธิ',
        'status' => 'สถานะ',
        'remarks' => 'หมายเหตุเพิ่มเติม',
        'internal_note' => 'บันทึกภายใน',
        'linked_billings' => 'บิลที่เชื่อมโยง',
        'additional_information' => 'ข้อมูลเพิ่มเติม',
        'outstanding_invoices' => 'ใบแจ้งหนี้ค้างชำระ',
    ],

    'credit_notes' => [
        'navLabel' => 'เครดิตโน้ต',
        'modelLabel' => 'เครดิตโน้ต',
        'pluralModelLabel' => 'เครดิตโน้ต',
        'credit_note_number' => 'หมายเลขใบลดหนี้',
        'issued_by' => 'ออกโดย',
        'issued_at' => 'วันที่ออก',
        'reason' => 'เหตุผล',
        'invoice_total' => 'ยอดรวมใบแจ้งหนี้',
        'credit_note_total' => 'ยอดรวมใบลดหนี้',
        'type' => 'พิมพ์',
        'status' => 'สถานะ',
        'credit_note_details' => 'รายละเอียดใบลดหนี้',
    ],

    'credit_note_details' => [
        'description' => 'รายละเอียด',
        'amount' => 'จำนวนเงิน',
    ],
    'charge_by' => 'คิดค่าบริการโดย',
    'unit_maintenance' => 'ค่าบำรุงรักษาหน่วย',
    'unit_maintenance_summaries' => 'สรุปค่าบำรุงรักษาหน่วย',

];
