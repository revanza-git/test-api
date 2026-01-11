New user created

ID: {{ $user->id }}
Name: {{ $user->name }}
Email: {{ $user->email }}
Created at: {{ $user->created_at?->toISOString() }}

