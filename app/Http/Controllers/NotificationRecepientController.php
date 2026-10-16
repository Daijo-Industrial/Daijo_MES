<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use App\Models\NotificationType;
use Illuminate\Http\Request;

class NotificationRecepientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $recipients = NotificationRecipient::all();

        return view('notification_recipients.index', compact('recipients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $notificationTypes = NotificationType::all(); // Fetch all notification types

        return view('notification_recipients.create', compact('notificationTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:notification_recipients,email',
            'notification_type_id' => 'required|exists:notification_types,id',
            'active' => 'required|boolean',
        ]);

        NotificationRecipient::create($request->all());

        return redirect()->route('notification_recipients.index')->with('success', 'Recipient added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $notificationRecipient = NotificationRecipient::find($id);
        $notificationTypes = NotificationType::all(); // Fetch all notification types

        return view('notification_recipients.edit', compact('notificationRecipient', 'notificationTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notificationRecipient = NotificationRecipient::find($id);

        $request->validate([
            'email' => 'required|email|unique:notification_recipients,email,' . $notificationRecipient->id,
            'notification_type_id' => 'required|exists:notification_types,id',
            'active' => 'required|boolean',
        ]);

        $notificationRecipient->update($request->all());

        return redirect()->route('notification_recipients.index')->with('success', 'Recipient updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notificationRecipient = NotificationRecipient::find($id);
        $notificationRecipient->delete();

        return redirect()->route('notification_recipients.index')->with('success', 'Recipient deleted successfully.');
    }
}
