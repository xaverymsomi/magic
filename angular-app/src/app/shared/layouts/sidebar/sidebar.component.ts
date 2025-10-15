import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { User } from '../../models/auth.models';
import { MenuService, MenuItem } from '../../../core/services/menu.service';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <!-- Logo Section - Migrated from views/body.php lines 6-8 -->
    <div id="mabrexLogoPlaceholder" class="hidden-sm hidden-xs bg-info" [class.collapsed]="isCollapsed">
      <img src="/assets/images/rahisi/official_rahisi_logo_coloured.png" class="img img-responsive">
    </div>
    
    <!-- Sidebar Navigation - Migrated from views/body.php lines 9-28 -->
    <div id="mabrexSideNavBar" 
         style="height: 100dvh;" 
         class="hidden-sm hidden-xs scrolled-div"
         [class.collapsed]="isCollapsed">
      <ul class="list-group">
        <li *ngFor="let menu of menus" class="list-group-item">
          <a (click)="loadPage(menu.link, menu.title, menu.id)" 
             class="page-link" 
             [attr.data-link]="menu.link" 
             [attr.title]="menu.title">
            <i class="pe pe-7s-{{menu.icon}} pe-2x pe-va pe-fw"></i>
            <span> {{menu.name}} </span>
            <i class="pe pe-7s-angle-right pe-2x pe-va pe-fw pull-right" 
               *ngIf="menu.submenus.length > 0 && current !== menu.id"></i>
            <i class="pe pe-7s-angle-down pe-2x pe-va pe-fw pull-right" 
               *ngIf="menu.submenus.length > 0 && current === menu.id"></i>
          </a>
          
          <ul class="mabrex-submenu" *ngIf="current === menu.id && menu.submenus.length > 0">
            <li *ngFor="let sub of menu.submenus">
              <a (click)="loadPage(sub.link, sub.title, menu.id)" 
                 class="page-link" 
                 [attr.data-link]="sub.link" 
                 [attr.title]="sub.title">
                <i class="pe{{sub.icon}} pe-va pe-fw"></i> {{sub.name}}
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- Mobile Logo - Migrated from views/body.php lines 30-32 -->
    <div id="mabrexLogoPlaceholderMobile" class="hidden-md hidden-lg bg-info" style="padding-left: 7px;">
      <img src="/assets/images/rahisi/official_rahisi_minimal_logo_coloured.png">
    </div>
    
    <!-- Mobile Sidebar - Migrated from views/body.php lines 33-49 -->
    <div id="mabrexSideNavBarSmall" class="hidden-md hidden-lg">
      <ul class="list-group">
        <li *ngFor="let menu of menus" class="list-group-item">
          <a (click)="loadPage(menu.link, menu.title, menu.id)" 
             class="page-link" 
             [attr.data-link]="menu.link" 
             [attr.title]="menu.title">
            <i class="pe pe-7s-{{menu.icon}} pe-2x pe-va" 
               [class]="(current === menu.id || current_link === menu.id) ? ' ' : ''"></i>
          </a>
          
          <ul class="mabrex-submenuSmall" 
              *ngIf="current === menu.id && menu.submenus.length > 0 && current_task === 'display'">
            <li *ngFor="let sub of menu.submenus">
              <a (click)="loadPage(sub.link, sub.title, menu.id); setSubMenu(menu.id)" 
                 class="page-link" 
                 [attr.data-link]="sub.link" 
                 [attr.title]="sub.title">
                <i class="pe {{sub.icon}} pe-2x pe-va pe-fw"></i> {{sub.name}}
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  `,
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {
  @Input() isCollapsed = false;
  @Input() user: User | null = null;

  menus: MenuItem[] = [];
  current = '';
  current_link = '';
  current_task = 'display';

  constructor(
    private menuService: MenuService,
    private router: Router
  ) {}

  ngOnInit(): void {
    if (this.user) {
      this.getUserMenu(this.user.id);
    }
    // Initialize with no menu expanded
    this.current = '';
  }

  getUserMenu(userId: string | number): void {
    this.menuService.getUserMenu(userId).subscribe(
      menus => {
        this.menus = menus;
      },
      error => {
        console.error('Failed to load user menu:', error);
      }
    );
  }

  loadPage(link: string, title: string, menuId: string): void {
    // Toggle submenu if clicking on same menu item
    if (this.current === menuId) {
      this.current = '';
    } else {
      this.current = menuId;
    }
    this.current_link = menuId;
    
    // Navigate to the route
    this.router.navigate([link]).catch(error => {
      console.error('Navigation failed:', error);
      // If route doesn't exist, stay on current page
    });
  }

  setSubMenu(menuId: string): void {
    // TODO: Implement submenu logic for mobile
    console.log('Set submenu:', menuId);
  }
}
