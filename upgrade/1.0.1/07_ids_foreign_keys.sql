-- Prepares table having uid or pid set to 0.
UPDATE aliases SET uid = NULL WHERE uid = 0;
UPDATE log_sessions SET uid = NULL WHERE uid = 0;
UPDATE log_sessions SET suid = NULL WHERE suid = 0;
UPDATE register_marketing SET sender = NULL WHERE sender = 0;
UPDATE register_mstats SET sender = NULL WHERE sender = 0;
UPDATE requests SET pid = NULL WHERE pid = 0;
UPDATE profile_addresses SET pid = NULL WHERE pid = 0;
UPDATE survey_votes SET uid = NULL WHERE uid = 0;

-- Deltes erroneous data.
DELETE FROM homonyms WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = homonyms.homonyme_id);
DELETE FROM register_marketing WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = register_marketing.uid);
DELETE FROM watch_nonins WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = watch_nonins.ni_id);
DELETE FROM log_last_sessions WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = log_last_sessions.uid);
DELETE FROM forum_profiles WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = forum_profiles.uid);
DELETE FROM watch WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = watch.uid);
DELETE FROM group_event_participants WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = group_event_participants.uid);
DELETE FROM group_members WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = group_members.uid);
DELETE FROM axletter_ins WHERE NOT EXISTS (SELECT * FROM accounts WHERE accounts.uid = axletter_ins.uid);

-- Following tables all refer to accounts.uid.
ALTER TABLE account_auth_openid ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE account_lost_passwords ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE account_profiles ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aliases ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE announce_read ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE announces ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE axletter_ins ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE axletter_rights ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE carvas ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE contacts ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE email_list_moderate ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE email_options ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE email_send_save ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE email_watch ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE forum_innd ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE forum_profiles ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE forum_subs ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_announces ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_announces_read ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_events ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_event_participants ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_member_sub_requests ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE group_members ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE homonyms ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE ip_watch ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE log_last_sessions ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE log_sessions ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE newsletter_ins ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE payment_transactions ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_marketing ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_mstats ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_pending ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_subs ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE reminder ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE requests ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE requests_hidden ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE survey_votes ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE surveys ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE watch ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE watch_nonins ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE watch_promo ADD FOREIGN KEY (uid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Following tables all refer to accounts.uid, but they use a different name.
ALTER TABLE contacts ADD FOREIGN KEY (contact) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE gapps_accounts ADD FOREIGN KEY (l_userid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE gapps_queue ADD FOREIGN KEY (q_owner_id) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE gapps_queue ADD FOREIGN KEY (q_recipient_id) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE gapps_nicknames ADD FOREIGN KEY (l_userid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE homonyms ADD FOREIGN KEY (homonyme_id) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE log_sessions ADD FOREIGN KEY (suid) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_marketing ADD FOREIGN KEY (sender) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE register_mstats ADD FOREIGN KEY (sender) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE watch_nonins ADD FOREIGN KEY (ni_id) REFERENCES accounts (uid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Following tables all refer to profiles.pid.
ALTER TABLE account_profiles ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE requests ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE search_name ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE watch_profile ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_binets ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_corps ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_display ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_education ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_job ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_langskills ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_medals ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_mentor ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_mentor_country ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_mentor_sector ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_name ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_networking ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_phones ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_photos ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE profile_skills ADD FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE;

-- vim:set syntax=mysql:
