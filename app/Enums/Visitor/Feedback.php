<?php

namespace App\Enums\Visitor;

enum Feedback: string
{
    case REMEMBER_WRONG = 'Visitor remember wrong';
    case REMEMBER_WRONG_TH = 'ผู้มาติดต่อแจ้งผิด';
    case PRESS_WRONG = 'Security guard press wrong';
    case PRESS_WRONG_TH = 'รปภ. กดผิด';
    case IMPERSONATION = 'Impersonation';
    case IMPERSONATION_TH = 'มีผู้แอบอ้าง';
    case OTHER = 'Other';
    case OTEHR_TH = 'อื่นๆ';
}
