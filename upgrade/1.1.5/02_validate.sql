ALTER TABLE  requests_answers MODIFY COLUMN  category ENUM('alias','liste','usage','photo','evts','gapps-unsuspend','marketing','orange','homonyme','nl','paiements','medal','broken','surveys', 'entreprise','account','address','bulkaccounts') NOT NULL DEFAULT 'alias';

-- vim:set syntax=mysql:
