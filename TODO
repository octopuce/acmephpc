
Question:
    - how shall we register a punnycode / unicode domain name?
        - with punnycode version? unicode one? not at all? 

Test Scenarios:
- newReg with new info
- newReg with existing email or tel
- newReg with bad information (unknown key)

- getReg with existing ID
- getReg with non-existing ID

- newAuthz with proper info
- newAuthz with incorrect info
- newAuthz with same domain name as existing one ? (how does boulder react to this?)

- Challenge with incorrect answer from plugin
- Challenge with correct answer from plugin
- Challenge with unknown plugin
- Challenge on already-solved challenged (how does boulder react to this?)

- newCert with proper info
- newCert with incorrect info
- newCert with non-validated authz
- newCert with same domain name as existing one ? (how does boulder react to this?)

- revokeCert with proper info
- revokeCert with incorrect info
- revokeCert with already-revokes cert (how does boulder react to this?)

and
- any API stdCall with non-initialized Reg? (apart from new/get-reg ofc)


>> see if the API/RFC tells which answer the CA/ACME Server should reply when doing those 
"same domain" or "already revoked" calls.

>> manage status of Authz, Challenges ? & Certs 
