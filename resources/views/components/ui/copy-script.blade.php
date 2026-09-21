{{--
    Copy-to-clipboard, everywhere it is offered.

    The async Clipboard API only exists in a secure context. Served over plain
    HTTP to anything but localhost — a phone on the office wifi, a staging box,
    an IP address — `navigator.clipboard` is undefined, and reading `.writeText`
    off it throws before anything is copied. That is why the buttons did nothing
    at all rather than failing visibly: the exception landed before the "Copied"
    flag was ever set.

    So: try the modern API, fall back to the old synchronous one that works over
    HTTP, and say so when both refuse rather than claiming success.
--}}
@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    async function writeToClipboard(text) {
        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(text);

                return true;
            } catch (e) {
                // Denied, or the document was not focused. Try the old way.
            }
        }

        // execCommand is deprecated and still the only thing that works on an
        // insecure origin. Off-screen rather than hidden: a field that is not
        // rendered cannot be selected.
        const field = document.createElement('textarea');

        field.value = text;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.top = '-9999px';
        document.body.appendChild(field);
        field.select();

        let copied = false;

        try {
            copied = document.execCommand('copy');
        } catch (e) {
            copied = false;
        }

        document.body.removeChild(field);

        return copied;
    }

    Alpine.data('copyable', (text) => ({
        copied: false,
        failed: false,

        async copy() {
            const ok = await writeToClipboard(text);

            this.copied = ok;
            this.failed = ! ok;

            setTimeout(() => {
                this.copied = false;
                this.failed = false;
            }, 2000);
        },
    }));
});
</script>
@endpush
@endonce
