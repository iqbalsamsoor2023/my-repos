<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => 'required|integer',
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required_without:comment_image|string',
            'comment_image' => 'required_without:content|mimes:png,jpg,jpeg,gif|max:20480',
        ];
    }
}
