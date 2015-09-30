
Question:
    - how shall we register a punnycode / unicode domain name?
        - with punnycode version? unicode one? not at all? 

Test Scenarios:
- newReg with new info
- newReg with existing email or tel
- newReg with bad information (unknown key)

- getReg with existing ID
- getReg with non-existing ID

- newAuthz with info
- newAuthz with similar domain name ? (how does boulder react to this?)


and
- any API stdCall with non-initialized Reg? (apart from new/get-reg ofc)
