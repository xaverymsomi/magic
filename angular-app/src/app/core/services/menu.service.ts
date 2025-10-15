import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface MenuItem {
  id: string;
  name: string;
  icon: string;
  route?: string;
  permission?: string;
  children?: MenuItem[];
}

@Injectable({
  providedIn: 'root'
})
export class MenuService {
  private menuItemsSubject = new BehaviorSubject<MenuItem[]>([]);
  public menuItems$ = this.menuItemsSubject.asObservable();

  constructor(private http: HttpClient) {}

  // Preserve menuController::getUserMenu() logic
  getUserMenu(userId: string): Observable<any> {
    return this.http.get(`${environment.apiUrl}/menu/get_user_menus?user_id=${userId}`);
  }

  // Load menu items based on user permissions
  loadUserMenu(userId: string): void {
    this.getUserMenu(userId).subscribe({
      next: (response: any) => {
        if (response.data) {
          this.menuItemsSubject.next(response.data);
        }
      },
      error: (error) => {
        console.error('Failed to load user menu:', error);
      }
    });
  }

  // Get current menu items
  getMenuItems(): MenuItem[] {
    return this.menuItemsSubject.value;
  }
}
