lmao
====

LMAO is the Lightweight Mail Administration O...

The `examples` directory contains:
 - some documentation about the LDAP structures I use to store mail aliases, mailbox information and credentials.
 - configuration examples for postfix and dovecot showing how to set up:
   1. One or more (mx) mail filtering server that accept mail from the world and are listed as MX for domains in DNS. All postfix domain, alias and relay maps are directly read from one or more LDAP servers (which can be done using syncrepl replication).
   2. An SMTP server for outgoing mail relay that also accepts submission/587 with TLS for mail submission from a MUA using mailbox credentials.
   3. A Dovecot IMAP server that's using the mailbox information in LDAP to authenticate users and can work with mailboxes in multiple domains.

Never mix the roles of handling incoming and outgoing mail in one postfix instance, just like you would not mix a DNS authoritative server and resolver. You'll get in trouble during migrations of customers to or from your incoming mailfiltering if you mix them.

TODO:
 - write more documentation, explaining the config
 - write a new web frontend for management by end users who can manage a mail domain
 - ...

 Hans van Kranenburg, <hans@knorrie.org>
