<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', [
            'messages' => ContactMessage::query()->latest()->paginate(20),
            'subscriberCount' => NewsletterSubscriber::query()->count(),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', compact('message'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Message supprimé.');
    }

    public function subscribers(): View
    {
        return view('admin.messages.subscribers', [
            'subscribers' => NewsletterSubscriber::query()->latest()->paginate(50),
        ]);
    }

    /**
     * Neutralises spreadsheet formulas (=, @, tab, CR) in exported cells; phone numbers keep their leading +.
     */
    private static function csvSafe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=@\t\r]/', $value) ? "'".$value : $value;
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['telephone', 'email', 'inscrit_le']);
            NewsletterSubscriber::query()->orderBy('id')->each(fn (NewsletterSubscriber $subscriber) => fputcsv($out, [self::csvSafe($subscriber->phone), self::csvSafe($subscriber->email), $subscriber->created_at->toDateTimeString()]));
            fclose($out);
        }, 'abonnes-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
