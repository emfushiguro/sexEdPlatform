# Host event and attendance interface refinements

## Implemented

- Capacity help explains that the organization chooses its registration limit according to its arrangements and external platform capacity. Blank means no registration limit set in this application. Existing capacity validation and confirmed-registration safeguards remain.
- Host event details use a compact landscape thumbnail on the left, with status, title, Description, optional Objectives and actions on the right. Mobile stacks these regions. The schedule and registration summary remain below.
- Comments and Q&A are hidden for external and in-person events. Legacy native livestreams retain their existing discussion UI.
- Recent Registrants, registrant details and the full registrants list show exact registration timestamps converted to the configured display timezone (PHT by default), including seconds. The previous relative and unconverted UTC formatting is removed.
- The shared external/in-person attendance roster omits Joined, Left and Duration. Recorded by remains. Recorded at converts to PHT, and manual absence decisions show their recording time instead of an empty attended-at field. Native livestream telemetry remains available for legacy events.
- Each active confirmed registrant has one attendance decision icon in its row. It opens a labelled native dialog with that registrant's form, decision and correction/removal reason. Server validation and audit rules remain. Validation errors reopen the submitted row's dialog with safe old values; keyboard Escape and Close return to the trigger through native dialog behavior.
- Attendance code opening and closing fields move into a labelled native dialog. Generation/regeneration still uses the existing authorized endpoint. The dialog explains defaults and that regeneration invalidates the previous code and removes its post. Window errors reopen the dialog; code copying, presentation, posting/unposting and closing remain on the main section.

## Scope and checks

Current branch and existing data preserved. No schema or stored timestamp changes. The attendance CSV now also omits Joined at, Left at and Total minutes for external/in-person events in both host and admin exports; legacy native exports retain these supported columns. Existing assertions were updated to the new capacity copy and CSV column contract; no new tests were added or run for this refinement. Blade compilation, static review, scoped whitespace checks and the frontend build are the checks for the UI change; PHP syntax and scoped Pint cover the CSV follow-up. Prior test counts belong to the earlier thumbnail implementation.

## Manual QA

1. Open a host event on desktop and mobile. Check the smaller image and the status/title/Description/Objectives beside it on desktop. Confirm external/in-person events have no Comments or Q&A cards.
2. Open create/edit. Capacity help should describe the host's own decision and distinguish the application's registration limit from the external platform's capacity.
3. Compare a known registration and code submission timestamp with the application's PHT display. Recent Registrants and the full list should agree. A manually recorded absence should show the recording time.
4. Open Attendance. External/in-person rows should omit session telemetry and show a single decision icon for each active confirmed registration. Open two different registrants in turn and verify each form action and participant label match the selected row.
5. Change a decision to Not present. A reason must be required. Submit an invalid correction in QA, then confirm the same dialog reopens with its error and entered values. Check Close, Escape and keyboard focus return.
6. Open Attendance settings. Check existing custom times, blank defaults, Close/Escape and visible regeneration notice. On a QA event, generate/regenerate and verify the configured window and new-code sharing flow. Do not regenerate real event codes just to inspect the dialog.

Full browser interaction, automated application tests, staging and physical-device QA remain unrun for these refinements.
