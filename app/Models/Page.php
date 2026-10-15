<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A custom content page of a branch store (About, Privacy, Return policy…). */
class Page extends Model
{
    public const TEMPLATES = [
        'about-us' => ['About Us', '<h2>আমাদের সম্পর্কে</h2><p>আমরা মানসম্মত পণ্য সবচেয়ে ভালো দামে আপনার দোরগোড়ায় পৌঁছে দিই।</p>'],
        'privacy-policy' => ['Privacy Policy', '<h2>গোপনীয়তা নীতি</h2><p>আপনার নাম, ফোন ও ঠিকানা শুধু অর্ডার ডেলিভারি ও যোগাযোগের জন্য ব্যবহার করা হয়। আমরা কারো সাথে আপনার তথ্য বিক্রি বা শেয়ার করি না (কুরিয়ার ছাড়া)।</p>'],
        'terms-conditions' => ['Terms & Conditions', '<h2>শর্তাবলী</h2><p>অর্ডার করার মাধ্যমে আপনি আমাদের মূল্য, ডেলিভারি ও রিটার্ন নীতিতে সম্মত হচ্ছেন।</p>'],
        'return-policy' => ['Return & Refund Policy', '<h2>রিটার্ন ও রিফান্ড নীতি</h2><p>পণ্য হাতে পাওয়ার ৭ দিনের মধ্যে অব্যবহৃত অবস্থায় রিটার্ন করা যাবে। রিফান্ড ৩-৭ কর্মদিবসের মধ্যে দেওয়া হয়।</p>'],
        'delivery-info' => ['Delivery Information', '<h2>ডেলিভারি তথ্য</h2><p>ঢাকার ভিতরে ১-২ দিন, ঢাকার বাইরে ২-৪ দিন। ক্যাশ অন ডেলিভারি সুবিধা আছে।</p>'],
        'faq' => ['FAQ', '<h2>সাধারণ জিজ্ঞাসা</h2><h3>কীভাবে অর্ডার করব?</h3><p>পণ্য কার্টে যোগ করে চেকআউট করুন।</p>'],
    ];

    protected $fillable = ['branch_id', 'title', 'slug', 'content', 'is_published', 'show_in_header', 'show_in_footer', 'sort', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'show_in_header' => 'boolean', 'show_in_footer' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function url(): string
    {
        return route('store.page', [$this->branch, $this->slug]);
    }
}
