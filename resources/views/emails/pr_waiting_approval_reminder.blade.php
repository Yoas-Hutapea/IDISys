<p>Bersama ini kami sampaikan pengingat (reminder) bahwa terdapat Purchase Request yang menunggu tindakan Anda:</p>

<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 500px;">
    <tr>
        <td><strong>PR Number</strong></td>
        <td>{{ $prNumber }}</td>
    </tr>
    <tr>
        <td><strong>Status</strong></td>
        <td>{{ $statusLabel }}</td>
    </tr>
</table>

<p style="margin-top: 24px;">
    Silakan login ke IDEAsys untuk melakukan {{ $recipientRole }} (Review / Approval) agar status PR dapat dilanjutkan.
</p>

<p style="margin-top: 24px;">
    <strong>IDEAsys Notification</strong>
</p>
<p style="color: #666; font-size: 12px;">
    <em>This is an IDEAsys automatic e-mail, please do not reply</em>
</p>
