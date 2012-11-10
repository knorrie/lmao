phplmao
=======

Here's the source code of the old php/ldap mailalias/mailbox management web gui
I wrote somewhere around 2005. Please do not use it. It's archived here to pick
some ideas from it when building a new version.

It can create, remove and modify alias definitions and create, modify and
delete mailbox credentials. The scripts directly connect to LDAP. There's no
undo or the like.

Functionality to add and remove alias or mailbox domains or edit users or
permissions was never implemented, so that had to be done directly in LDAP.
