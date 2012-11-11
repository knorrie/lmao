lmao
====

LMAO is the Lightweight Mail Administration O...

What you're not going to find here is:
 - some howto you can follow by copy pasting commands
 - some easy installable tool to manage all your mail business for you so you don't need to spend lots of time learning stuff

What you're going to find here is:
 - an example of a complete, custom postfix/ldap/dovecot setup that has actually already been running in production for years
 - some added documentation that describes details about the setup
...which might be fun to look at if you want to explore, or evaluate what possibilities using the combination of this software has. You might find information and examples that are useful to you, either because you can use them or because you discover you don't want to use them like I do and do something else insteadthat better fits your needs.

### Why Postfix? ###

Well, for some reason in the past it seems I chose to use Postfix. And I never regretted that choice. Configuration is easy to read and maintain. no weird cryptic stuff, no need for a headache if you look at configuration you haven't touched for some time. Documentation is really great. The author is a great programmer. If I ever need to learn C programming really good, I think I'd start reading the postfix source code.

### Why LDAP? ###

Like probably lots of other people, I have a hate/love relationship with LDAP. You can do cool things using LDAP, but it's horribly complicated. The recent move from a text configuration file you can just edit with vim and put into revision control with etckeeper to an overly complex way of storing the configuration file contents inside the ldap database itself, resulting in a configuration mine field for the user, is imho a very good example of how not to improve software to be more usable. It gives me the same feeling like being forced to write all of your configuration and documents using ed instead of vim from now on!

But... LDAP is great, because it has multi-value attributes! Using LDAP we can create mail alias objects that can have multiple addresses to receive mail on, and forward it to.

So instead of having to maintain a list like this (which can very quickly become an inconsistent mess):

    postmaster -> admin
    abuse -> admin
    hostmaster -> admin
    jobs -> jill,pete
    office -> john
    hiring -> jobs
    admin -> rick
    building -> office
	jill
	john
	rick
	pete

We can do it like this, e.g. focusing on the actual people that need to receive messages:

    Jill receives mail sent to jill, jobs and hiring
    Pete receives mail sent to pete, jobs and hiring
    Rick receives mail sent to rick, admin, postmaster, hostmaster and abuse
    John receives mail sent to john, office and building

Or do some mixup of both:

    Jill receives mail sent to jill
    Pete receives mail sent to pete
    Jill and Pete receive mail sent to jobs and hiring
    Rick receives mail sent to rick, admin, postmaster, hostmaster and abuse
    John receives mail sent to john and office

The alias management helper (some gui?) should have functionality to select a destination address, and show who actually gets email for this address. Retrieving that information can still be done using a single ldap query.

If you don't care about this, you can always use SQL maps (e.g. with PostgreSQL). Unless of course... you like learning difficult things. Knowledge of LDAP/openldap is one of the things that should be present in every sysadmins 'toolkit' anyway.

And, last but not least, I like the way openldap does replication with the syncrepl protocol.

### So, where's the money, Lebowski? ###

The examples directory contains:
 - some documentation about the LDAP structures I use to store mail aliases, mailbox information and credentials.
 - configuration examples for postfix and dovecot showing how to set up:
   1. One or more (mx) mail filtering server that accept mail from the world and are listed as MX for domains in DNS. All postfix domain, alias and relay maps are directly read from one or more LDAP servers.
   2. An SMTP server for outgoing mail relay that also accepts submission/587 with TLS for mail submission from a MUA using mailbox credentials.
   3. A Dovecot IMAP server that's using the mailbox information in LDAP to authenticate users and can work with mailboxes in multiple domains.

Never mix the roles of handling incoming and outgoing mail in one postfix instance, just like you would not mix a DNS authoritative server and resolver. You'll get in trouble during migrations of customers to or from your incoming mailfiltering if you mix them.

TODO:
 - write more documentation, explaining the config
 - write a new web frontend for management by end users who can manage a mail domain? (simple, no feature creep please)
 - ...

Hans van Kranenburg, <hans@knorrie.org>
