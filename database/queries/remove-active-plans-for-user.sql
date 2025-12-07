-- One-time SQL query to remove all active plans for a specific user
-- Replace 'sujood.malkawi993@gmail.com' with the actual email
-- Replace 8 with the actual org_id

-- Step 1: Find the user ID
SELECT id, fullName, email 
FROM orgUser 
WHERE email = 'sujood.malkawi993@gmail.com' 
  AND org_id = 8;

-- Step 2: View active plans before removal (for verification)
SELECT 
    oup.id,
    oup.orgPlan_id,
    oup.status,
    oup.startDateLoc,
    oup.endDateLoc,
    oup.price,
    oup.currency,
    oup.isCanceled,
    oup.isDeleted
FROM orgUserPlan oup
INNER JOIN orgUser ou ON oup.orgUser_id = ou.id
WHERE ou.email = 'sujood.malkawi993@gmail.com'
  AND ou.org_id = 8
  AND oup.org_id = 8
  AND oup.status IN (1, 2, 6)  -- STATUS_UPCOMING=1, STATUS_ACTIVE=2, STATUS_PENDING=6
  AND oup.isCanceled = 0
  AND oup.isDeleted = 0;

-- Step 3: Remove all active plans (set status to CANCELED and mark as canceled)
UPDATE orgUserPlan oup
INNER JOIN orgUser ou ON oup.orgUser_id = ou.id
SET 
    oup.status = 4,  -- STATUS_CANCELED = 4
    oup.isCanceled = 1,
    oup.updated_at = UNIX_TIMESTAMP()
WHERE ou.email = 'sujood.malkawi993@gmail.com'
  AND ou.org_id = 8
  AND oup.org_id = 8
  AND oup.status IN (1, 2, 6)  -- STATUS_UPCOMING=1, STATUS_ACTIVE=2, STATUS_PENDING=6
  AND oup.isCanceled = 0
  AND oup.isDeleted = 0;

-- Step 4: Verify removal (should return 0 rows)
SELECT COUNT(*) as remaining_active_plans
FROM orgUserPlan oup
INNER JOIN orgUser ou ON oup.orgUser_id = ou.id
WHERE ou.email = 'sujood.malkawi993@gmail.com'
  AND ou.org_id = 8
  AND oup.org_id = 8
  AND oup.status IN (1, 2, 6)
  AND oup.isCanceled = 0
  AND oup.isDeleted = 0;


