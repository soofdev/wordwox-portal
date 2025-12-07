<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactForm extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $subject = '';
    public $message = '';
    public $template = 'fitness';
    
    public $showSuccessMessage = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'subject' => 'nullable|string|max:255',
        'message' => 'required|string|max:2000',
    ];

    protected $messages = [
        'name.required' => 'Name is required.',
        'email.required' => 'Email is required.',
        'email.email' => 'Please enter a valid email address.',
        'message.required' => 'Message is required.',
    ];

    public function submit()
    {
        $this->validate();

        try {
            // Log the contact form submission
            Log::info('Contact form submitted', [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'subject' => $this->subject,
                'message' => substr($this->message, 0, 100) . '...',
                'timestamp' => now(),
                'ip' => request()->ip()
            ]);

            // Here you could send an email notification
            // For now, we'll just show a success message
            $this->showSuccessMessage = true;
            
            // Reset form
            $this->reset(['name', 'email', 'phone', 'subject', 'message']);
            
        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $this->email
            ]);
            
            session()->flash('error', 'There was an error sending your message. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}