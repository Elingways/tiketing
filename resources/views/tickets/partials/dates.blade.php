<p class="text-[11px] text-slate-500">Request date {{ $ticket->created_at->format($format ?? 'd M Y, H:i') }}</p>
<p class="text-[11px] text-slate-500">Delivered date {{ $ticket->delivered_at ? $ticket->delivered_at->format($format ?? 'd M Y, H:i') : 'Belum diarahkan' }}</p>
