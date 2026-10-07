<?php

namespace App\Forms\Components\SupportTicket;

use Filament\Forms\Components\Field;

class SupportTicketFile extends Field
{
    protected string $view = 'forms.components.support-ticket.support-ticket-file';

    public function getAttachmentType()
    {
        $url = $this->getRecord()->attachment_url;

        if ($url) {
            return $this->guessMediaType($url);
        }

        return null;
    }

    protected function guessMediaType($url)
    {
        $extension = strtolower((string) pathinfo($url, PATHINFO_EXTENSION));

        $supportedExtensions = [
            'image' => ['png', 'jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp', 'gif', 'svg', 'webp', 'bmp', 'ico', 'avif', 'tif', 'tiff', 'cur', 'wmf', 'emf'],
            'video' => ['mpg', 'mpeg', 'mp4', 'webm', 'mov', 'avi', 'wmv', 'swf', 'flv', '3gp', 'qt', 'm4p', 'mp2'],
            'audio' => ['mp3', 'ogg', 'wav', 'pcm', 'aac', 'opus'],
            'excel' => ['xlsx', 'xls', 'xlsm', 'xlsb', 'xltx', 'xltm', 'xlt', 'xml', 'xlam', 'xlc', 'xlt', 'xld', 'xlk', 'xla', 'xlw', 'xlr', 'ods', 'csv'],
            'pdf' => ['pdf'],
            'doc' => ['doc', 'docx', 'docm'],
            'ppt' => ['ppt', 'pptx', 'pptm'],
            'zip' => ['zip', 'tar', 'tar.gz', 'bz', 'bz2', 'gz', '7z', 'tgz'],
            'rar' => ['rar'],
            'text' => ['txt', 'prn', 'dif'],
        ];

        foreach ($supportedExtensions as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return null;
    }
}
