<h2>{{ $subjectLine }}</h2>
<p><strong>Ticket ID:</strong> {{ $ticket->id }}</p>
<p><strong>Title:</strong> {{ $ticket->title }}</p>
<p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
<p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
<p><strong>Description:</strong></p>
<p>{{ $ticket->description }}</p>

<p>Regards,<br>Support Team</p>
