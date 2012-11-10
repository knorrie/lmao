LDAP Data Structures
====================

First of all, we need some container objects to hold our alias and relay recipient data. And yes, it's heavily Postfix-based. All Postfix mail server logic applies.

    dn: ou=virtual_alias_domains,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: virtual_alias_domains
    
    dn: ou=virtual_alias_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: virtual_alias_maps
    
    dn: ou=relay_domains,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: relay_domains
    
    dn: ou=relay_recipient_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: relay_recipient_maps

Mailbox domains and virtual mailbox maps are used _both_ by the mailfiltering (relay and relay recipient maps(!)) and by the IMAP servers (for user authentication).

    dn: ou=virtual_mailbox_domains,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: virtual_mailbox_domains
    
    dn: ou=virtual_mailbox_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: virtual_mailbox_maps

## Virtual Alias Domain ##

The following example illustrates the use of a virtual alias domain. The mailfiltering servers are final destination for alias domains, so we need to specify all valid email-addresses and map them somewhere else.

    dn: ou=example.net,ou=virtual_alias_domains,dc=example,dc=com
    objectClass: top
    objectClass: uidObject
    objectClass: organizationalUnit
    ou: example.net
    uid: example
    
    dn: ou=example.net,ou=virtual_alias_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: example.net
    
    dn: cn=Postmaster,ou=example.net,ou=virtual_alias_maps,dc=example,dc=com
    objectClass: top
    objectClass: person
    objectClass: inetOrgPerson
    cn: Postmaster
    sn: Postmaster
    uid: abuse@example.net
    uid: postmaster@example.net
    uid: hostmaster@example.net
    mail: postmaster@example.com

As you probably notice, the use of multiple source and/or destination addresses is possible, which makes for a very flexible way to organize our alias definitions.

## Relay Domain ##

Relay domain configuration defines a domain and its valid recipient addresses. Mail arrives, gets filtered and delivered to the next-hop.

    dn: ou=office.example.com,ou=relay_domains,dc=example,dc=com
    objectClass: top
    objectClass: uidObject
    objectClass: organizationalUnit
    ou: office.example.com
    uid: example
    l: relay:[office.example.com]:25
    
    dn: ou=office.example.com,ou=relay_recipient_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: office.example.com
    
    dn: cn=Mekker,ou=office.example.com,ou=relay_recipient_maps,dc=example,dc=com
    objectClass: top
    objectClass: person
    objectClass: inetOrgPerson
    cn: Mekker
    sn: Mekker
    uid: someaddress@office.example.com
    uid: othervalidadd@office.example.com

- uid attributes in relay\_recipient\_maps hold valid relay recipient addresses.
- the l attribute in the relay\_domains specification is used by Postfix as next-hop, the brackets mean no MX lookup is done, the A record is used (otherwise mail would loop back to the MX in this case, because the MX servers are MX for office.example.com)

### Using Recipient Address Verification ###

Whenever accepting mail for relay, we _must_ know about valid recipients to prevent our systems from generating backscatter. In special cases, where a list of valid relay recipients is not available, it's possible to use [recipient address verification](http://www.postfix.org/ADDRESS_VERIFICATION_README.html#recipient).

In the following example, only one address is specified, a wildcard address, `@foo.example.org`. By implementing an additional 'trick' in the Postfix config, Recipient Address Verification gets triggered:

    dn: cn=RAV,ou=foo.example.org,ou=relay_recipient_maps,dc=example,dc=com
    objectClass: top
    objectClass: person
    objectClass: inetOrgPerson
    cn: RAV
    sn: RAV
    uid: @foo.example.org

In `/etc/postfix/main.cf`:

    smtpd_restriction_classes =
        verify_relay
    
    verify_relay = reject_unverified_recipient
    
    smtpd_recipient_restrictions =
        [.. blah ..]
        check_recipient_access proxy:ldap:/var/local/mail/mx/ldap_check_recipient_access.verify_relay.cf

In `ldap_check_recipient_access.verify_relay.cf`:

    [...]
    search_base = ou=%d,ou=relay_recipient_maps,dc=example,dc=com
    scope = one
    query_filter = (uid=@%d)
    result_filter = verify_relay
    result_attribute = uid

Note: positive and negative lookups will be cached for a while, but our next hop really needs to be available to answer the probes.

## Dealing with HELO filtering and relay domains talking to our filtering server ##

All valid domains our mail filtering does know about will be used in HELO filtering:

    smtpd_helo_restrictions =
        permit_mynetworks
        reject_invalid_hostname
        reject_non_fqdn_hostname
        check_helo_access hash:/var/local/mail/mx/check_helo_access
        check_helo_access proxy:ldap:/var/local/mail/mx/ldap_check_helo_access_mailalias.cf
        check_helo_access proxy:ldap:/var/local/mail/mx/ldap_check_helo_access_mailrelay.cf

Normally, this is desired behaviour, we do not want someone to HELO with a domain name we're responsible for. Besides that, I'll never use domain names like 'example.com' in a HELO. Instead the real server name (and PTR lookup) should be used, like `smtp-out-5.example.net`. When relaying mail for specific **hosts**, this can lead to a problem. Imagine the following situation:

            | [A]                        ^ [D]
            V                            |
     +-----------------+  [B]  +--------------------+   [C]   +--------------------+
     | mx1.example.com | ----> | office.example.com | <-----> | internal           |
     | mx2.example.com |       +--------------------+         | office mailservice |
     +-----------------+                                      +--------------------+

Because:

    ~$ host -t mx office.example.com
    office.example.com mail is handled by 100 mx1.example.com.
    office.example.com mail is handled by 100 mx2.example.com.
    
    ~$ host -t mx example.com
    example.com mail is handled by 100 mx1.example.nl.
    example.com mail is handled by 100 mx2.example.nl.

 - [D] all outgoing mail from our office gets delivered to the world directly by `office.example.com`
 - [A] all incoming mail will be delivered to the filtering services
 - [B] mail with destination in relay domains like `example.com` will be filtered, and handed over to the mail server at the border of the office.
 - [C] mail will be delivered to an internal mailbox server afterwards. mail originating from the office should be [C] handled over to the office mailgateway, which delivers mail to the world [D]

What will happen when somebody in our office sends mail to an address in a domain outside the office, handled by the external mail filtering... [D] connects to [A], while using it's own name in the HELO, and filtering services will reject it... you see?

So we'll need another hack to exclude this name from HELO filtering:

    dn: ou=kantoor.example.nl,ou=relay_domains,dc=example,dc=com
    objectClass: top
    objectClass: uidObject
    objectClass: organizationalUnit
    ou: kantoor.example.nl
    uid: example
    l: relay:[kantoor.example.nl]:25
    st: no_check_helo_access

In `ldap_check_helo_access_mailrelay.cf`:

    [...]
    search_base = ou=relay_domains,dc=example,dc=com
    scope = one
    query_filter = (&(ou=%s)(!(st=no_check_helo_access)))
    result_filter = REJECT
    result_attribute = ou

Note the choice of LDAP attributes. I did like to use no extra schemas besides the standard schemas provided by OpenLDAP. So the semantics of attribute names and their values are somewhat inconsistent. Anyhow, it just works. ;-)

## Mailbox Domains ##

Mailbox domains specify valid relay recipients for whom mail will be forwarded to their mailbox on the imap server. At the filtering servers the `virtual_mailbox_domains` and `virtual_mailbox_maps` are included into `relay_domains` and `relay_recipient_maps`. You should understand why. If not, you need to figure out right now. At the IMAP server `virtual_mailbox_domains` and `virtual_mailbox_maps` are used to authenticate login attempts.

Multiple separate IMAP systems can be used by inserting an extra level of hierarchy into the ldap tree. IMAP servers can start searching at their own base dn, mail filtering and global SMTP Auth servers can search all of the mailbox domains.

    dn: ou=imap.example.com,ou=virtual_mailbox_domains,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: imap.example.com
    
    dn: ou=imap.example.com,ou=virtual_mailbox_maps,dc=example,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: imap.example.com

In this ou we can place the actual mailbox domains which will be delivered to this imap server, so the imap server can be given permissions to use ldap on the `ou=imap.example.com` level for checking logins.

    dn: ou=customer.example.com,ou=imap.example.com,ou=virtual_mailbox_domains,dc=e
     xample,dc=com
    objectClass: top
    objectClass: organizationalUnit
    objectClass: uidObject
    ou: customer.example.com
    l: relay:[imap.example.com]:25
    uid: example
    
    dn: ou=customer.example.com,ou=imap.example.com,ou=virtual_mailbox_maps,dc=exam
     ple,dc=com
    objectClass: top
    objectClass: organizationalUnit
    ou: customer.example.com
    
    dn: uid=test@customer.example.com,ou=customer.example.com,ou=imap.example.com,ou
     =virtual_mailbox_maps,dc=example,dc=com
    objectClass: top
    objectClass: person
    objectClass: organizationalPerson
    objectClass: inetOrgPerson
    uid: test@customer.example.com
    cn: Test mailbox
    userPassword: secret
    sn: bar

These records will be used as relay domain and relay recipient maps at the filtering servers.

Alias maps (**not** alias domains) can be used together with mailbox domains to provide additional address rewriting, mapping addresses to mailboxes or external addresses.

    dn: ou=customer.example.com,ou=imap.example.com,ou=customer_alias_maps,dc=example
     ,dc=com
    objectClass: top
    objectClass: comanizationalUnit
    ou: customer.example.com
    
    dn: cn=Additional alias,ou=customer.example.com,ou=imap.example.com,ou=customer_
     alias_maps,dc=example,dc=com
    objectClass: top
    objectClass: person
    objectClass: inetOrgPerson
    cn: Additional alias
    sn: Additional alias
    uid: bar@customer.example.com
    uid: baz@customer.example.com
    mail: test@customer.example.com
