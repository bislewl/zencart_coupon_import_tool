SET @configuration_group_id=0;
SELECT @configuration_group_id:=configuration_group_id
FROM configuration
WHERE configuration_key= 'COUPON_IMPORT_TOOL_VERSION'
LIMIT 1;
DELETE FROM configuration WHERE configuration_group_id = @configuration_group_id AND configuration_group_id <> 0;
DELETE FROM configuration_group WHERE configuration_group_id = @configuration_group_id AND configuration_group_id <> 0;
DELETE FROM admin_pages WHERE page_key = 'toolsCouponImportTool';
DELETE FROM admin_pages WHERE page_key = 'configCouponImportTool';