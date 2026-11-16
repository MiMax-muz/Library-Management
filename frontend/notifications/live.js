import {request} from '../shared/api.js';
import {currentUser} from '../shared/session.js';
import {mountNotifications} from './notifications.js';
export function mount({container,signal}){
 const load=async()=>{const items=[];let page=1;while(!signal.aborted){const r=await request('/notifications?page='+page,{signal});items.push(...r.data);if(items.length>=r.meta.total||!r.data.length)break;page++;}return items;};
 const source={mode:'api',load,markRead:async id=>{const r=await request('/notifications/'+id+'/read',{method:'PATCH',body:{},signal});window.dispatchEvent(new Event('notifications-updated'));return r.data;},markAllRead:async()=>{await request('/notifications/read-all',{method:'PATCH',body:{},signal});window.dispatchEvent(new Event('notifications-updated'));return load();}};
 return mountNotifications({container,signal,source,recipientName:currentUser().email});
}
