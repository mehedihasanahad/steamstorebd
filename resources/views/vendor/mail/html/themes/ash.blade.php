@php
    /*
     | The theme for Laravel's own markdown mail — the password reset link and
     | anything else sent as a MailMessage. Those messages are built from the
     | framework's markdown components rather than from our components, so they
     | are re-skinned here instead of rewritten.
     |
     | A `.blade.php` rather than the `.css` the framework ships because the
     | view finder tries `blade.php` first, which is what lets this file read
     | the same palette as every other message. A plain .css could only repeat
     | the hex by hand, and then there would be two places to change.
     */
    $c    = \App\Support\EmailTheme::palette();
    $font = \App\Support\EmailTheme::FONT;
@endphp
/* Base */

body,
body *:not(html):not(style):not(br):not(tr):not(code) {
    box-sizing: border-box;
    font-family: {!! $font !!}, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
    position: relative;
}

body {
    -webkit-text-size-adjust: none;
    background-color: {{ $c['canvas'] }};
    color: {{ $c['ink-mid'] }};
    height: 100%;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

p,
ul,
ol,
blockquote {
    line-height: 1.6;
    text-align: start;
}

a {
    color: {{ $c['accent-hover'] }};
}

a img {
    border: none;
}

/* Typography */

h1 {
    color: {{ $c['ink'] }};
    font-size: 22px;
    font-weight: bold;
    letter-spacing: -0.015em;
    margin-top: 0;
    text-align: start;
}

h2 {
    color: {{ $c['ink'] }};
    font-size: 17px;
    font-weight: bold;
    margin-top: 0;
    text-align: start;
}

h3 {
    color: {{ $c['ink'] }};
    font-size: 15px;
    font-weight: bold;
    margin-top: 0;
    text-align: left;
}

p {
    color: {{ $c['ink-mid'] }};
    font-size: 15px;
    line-height: 1.7;
    margin-top: 0;
    text-align: left;
}

p.sub {
    font-size: 13px;
}

img {
    max-width: 100%;
}

/* Layout */

.wrapper {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c['canvas'] }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 0;
    padding: 0;
    width: 100%;
}

/* Header */

.header {
    padding: 28px 0;
    text-align: center;
}

.header a {
    color: {{ $c['ink'] }};
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.015em;
    text-decoration: none;
}

/* Logo */

.logo {
    height: 64px;
    margin-top: 15px;
    margin-bottom: 10px;
    max-height: 64px;
    width: 64px;
}

/* Body */

.body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c['canvas'] }};
    border-bottom: 1px solid {{ $c['canvas'] }};
    border-top: 1px solid {{ $c['canvas'] }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    background-color: {{ $c['card'] }};
    border-color: {{ $c['border'] }};
    border-radius: 10px;
    border-style: solid;
    border-width: 1px;
    margin: 0 auto;
    padding: 0;
    width: 570px;
}

.inner-body a {
    color: {{ $c['accent-hover'] }};
    word-break: break-all;
}

/* Subcopy */

.subcopy {
    border-top: 1px solid {{ $c['border'] }};
    margin-top: 25px;
    padding-top: 25px;
}

.subcopy p {
    color: {{ $c['ink-low'] }};
    font-size: 13px;
}

/* Footer */

.footer {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 570px;
}

.footer p {
    color: {{ $c['ink-low'] }};
    font-size: 12px;
    text-align: center;
}

.footer a {
    color: {{ $c['ink-low'] }};
    text-decoration: underline;
}

/* Tables */

.table table {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid {{ $c['border'] }};
    color: {{ $c['ink-low'] }};
    margin: 0;
    padding-bottom: 8px;
}

.table td {
    color: {{ $c['ink-mid'] }};
    font-size: 15px;
    line-height: 20px;
    margin: 0;
    padding: 10px 0;
}

.content-cell {
    max-width: 100vw;
    padding: 32px;
}

/* Buttons */

.action {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    padding: 0;
    text-align: center;
    width: 100%;
    float: unset;
}

.button {
    -webkit-text-size-adjust: none;
    border-radius: 8px;
    color: {{ $c['ink'] }};
    display: inline-block;
    font-weight: 700;
    overflow: hidden;
    text-decoration: none;
}

.button-blue,
.button-primary {
    background-color: {{ $c['accent'] }};
    border-bottom: 8px solid {{ $c['accent'] }};
    border-left: 18px solid {{ $c['accent'] }};
    border-right: 18px solid {{ $c['accent'] }};
    border-top: 8px solid {{ $c['accent'] }};
}

.button-green,
.button-success {
    background-color: {{ $c['success-border'] }};
    border-bottom: 8px solid {{ $c['success-border'] }};
    border-left: 18px solid {{ $c['success-border'] }};
    border-right: 18px solid {{ $c['success-border'] }};
    border-top: 8px solid {{ $c['success-border'] }};
}

.button-red,
.button-error {
    background-color: {{ $c['danger-border'] }};
    border-bottom: 8px solid {{ $c['danger-border'] }};
    border-left: 18px solid {{ $c['danger-border'] }};
    border-right: 18px solid {{ $c['danger-border'] }};
    border-top: 8px solid {{ $c['danger-border'] }};
}

/* Panels */

.panel {
    border-left: {{ $c['accent'] }} solid 4px;
    margin: 21px 0;
}

.panel-content {
    background-color: {{ $c['raised'] }};
    color: {{ $c['ink-mid'] }};
    padding: 16px;
}

.panel-content p {
    color: {{ $c['ink-mid'] }};
}

.panel-item {
    padding: 0;
}

.panel-item p:last-of-type {
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Utilities */

.break-all {
    word-break: break-all;
}
