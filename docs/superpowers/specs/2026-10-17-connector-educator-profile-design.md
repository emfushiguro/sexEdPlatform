# Connector educator profile view

Approved in chat: add View beside Message in Connector Educators, reuse the existing instructor background presentation inside the Connector workspace, and make both actions icon buttons.

- Eye icon links to an authenticated Connector educator profile route. Message icon submits the existing CSRF-protected conversation action. Both have instructor-specific accessible labels, tooltips, keyboard focus and 44px targets; messaging retains its disabled/busy state.
- The profile uses the existing public instructor background Blade view with the Connector layout, deterministic Back to Educators link and Message icon action. Learner profile navigation and layout remain unchanged.
- Extract the existing public profile data normalization and credential filtering into one shared presentation service used by learner and Connector controllers. Read the same instructor-managed bio, photo, expertise, experience, education, credentials, certifications and professional background. Do not copy profile records or add schema.
- Require the same verified-Connector educator-manager access, including default seminar/webinar hosts. Only active directory instructors can be viewed. Ordinary members, outsiders, unverified Connectors and non-instructor targets are denied.
- Keep private verification credentials and account contact details out of the public presentation. Missing profiles use the existing empty states and do not create profile records.
- Validate profile values, privacy, current edits, access boundaries, empty states, icon markup and unchanged learner presentation with isolated tests; run existing profile and Connector messaging regressions.
