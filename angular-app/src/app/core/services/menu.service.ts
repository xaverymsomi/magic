import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { ApiService } from './api.service';

export interface MenuItem {
  id: string;
  name: string;
  title: string;
  link: string;
  icon: string;
  submenus: SubMenuItem[];
}

export interface SubMenuItem {
  name: string;
  title: string;
  link: string;
  icon: string;
}

@Injectable({
  providedIn: 'root'
})
export class MenuService {
  constructor(private apiService: ApiService) {}

  // Preserve getUserMenu logic from original AngularJS (app-angular.js)
  getUserMenu(userId: string | number): Observable<MenuItem[]> {
    // In the original AngularJS, this was called from menuController
    // Original endpoint: /menu/get_user_menus?user_id=
    return this.apiService.get(`/menu/get_user_menus?user_id=${userId}`).pipe(
      map((response: any) => {
        if (response && response.data) {
          return this.transformMenuData(response.data);
        }
        // Fallback to default menu structure if API fails
        return this.getDefaultMenu();
      }),
      catchError(error => {
        console.warn('Failed to load user menu, using default:', error);
        return of(this.getDefaultMenu());
      })
    );
  }

  private transformMenuData(menuData: any[]): MenuItem[] {
    return menuData.map(menu => ({
      id: menu.id.toString(),
      name: menu.name,
      title: menu.title || menu.name,
      link: menu.link,
      icon: menu.icon,
      submenus: menu.submenus ? menu.submenus.map((sub: any) => ({
        name: sub.name,
        title: sub.title || sub.name,
        link: sub.link,
        icon: sub.icon
      })) : []
    }));
  }

  // Default menu structure based on original AngularJS application
  private getDefaultMenu(): MenuItem[] {
    return [
      {
        id: '1',
        name: 'Home',
        title: 'Dashboard',
        link: '/dashboard',
        icon: 'home',
        submenus: []
      },
      {
        id: '2',
        name: 'Users',
        title: 'User Management',
        link: '/users',
        icon: 'users',
        submenus: [
          {
            name: 'All Users',
            title: 'View All Users',
            link: '/users/list',
            icon: 'pe-7s-users'
          },
          {
            name: 'Add User',
            title: 'Add New User',
            link: '/users/create',
            icon: 'pe-7s-user-plus'
          }
        ]
      },
      {
        id: '3',
        name: 'Reports',
        title: 'Reports',
        link: '/reports',
        icon: 'note',
        submenus: [
          {
            name: 'Generate Report',
            title: 'Generate New Report',
            link: '/reports/generate',
            icon: 'pe-7s-note2'
          },
          {
            name: 'View Reports',
            title: 'View All Reports',
            link: '/reports/list',
            icon: 'pe-7s-folder'
          }
        ]
      },
      {
        id: '4',
        name: 'Miscellaneous',
        title: 'Miscellaneous',
        link: '/misc',
        icon: 'config',
        submenus: []
      },
      {
        id: '5',
        name: 'Utility',
        title: 'Utility',
        link: '/utility',
        icon: 'tools',
        submenus: []
      }
    ];
  }
}
