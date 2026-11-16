<?php
require_once __DIR__.'/SettingsService.php';
return static function(Router $r,array $c):void{
 $r->add('GET','/api/settings',function()use($c){$p=Database::connection($c['db']);Auth::requireRoles($p);Response::json((new SettingsService($p))->values());});
 $r->add('PUT','/api/settings',function()use($c){$p=Database::connection($c['db']);Auth::requireRoles($p,['admin']);(new SettingsService($p))->update(Input::body());Response::json(['saved'=>true]);});
};
