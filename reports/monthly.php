<?php
/**
 * reports/monthly.php - إعادة توجيه إلى المركز المالي
 */
header("Location: financial-hub.php?tab=monthly" . ($_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : ''));
exit;
