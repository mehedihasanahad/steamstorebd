{{-- A label/value table. Rows are a table rather than flexbox because Outlook
     renders through Word, which has no flex and would stack every pair. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
{{ $slot }}
</table>
