# Frozen schema fixtures

`schema_v0_*.sql` are FROZEN snapshots of the install schemas as they stood at
`master` @ c588501 — before any migration existed.

**Never edit or regenerate these.** They are the "old install" that CI migrates
forward in order to prove:

    old schema + every migration  ==  what a fresh install produces today

If that stops being true, fresh installs and upgraded installs have become
different products. That is the failure these fixtures exist to catch, and it is
only catchable if the fixture stays frozen.

When a future schema change makes a NEW baseline worth freezing, add
`schema_v1_*.sql` alongside these — do not overwrite v0.
