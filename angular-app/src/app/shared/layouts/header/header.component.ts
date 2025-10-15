import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { User } from '../../models/auth.models';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <!-- Top Navigation Bar - Migrated from views/body.php lines 53-83 -->
    <div id="mabrexTopNavBar" class="navbar navbar-default navbar-fixed-top">
      <ul class="nav navbar-nav hidden-sm hidden-xs">
        <li id="mabrexMenuToggler" 
            style="margin: 3px; padding: 6px 2px 6px 20px; cursor: pointer;"
            (click)="onMenuToggle()">
          <i class="pe pe-7s-menu pull-left pe-2x pe-fw"></i>
        </li>
      </ul>
      
      <ul class="nav navbar-nav navbar-right">
        <li style="padding-top:15px;" class="hidden-xs" *ngIf="user">
          {{ user.username || user.email }}
        </li>
        
        <li class="dropdown pull-right">
          <a style="margin-right: 10px; padding: 11px 10px 7px 10px;" 
             href="#" 
             class="dropdown-toggle" 
             data-toggle="dropdown" 
             role="button" 
             aria-haspopup="true" 
             aria-expanded="false"
             (click)="toggleUserMenu($event)">
            <i class="pe pe-7s-user pe-2x pe-fw"></i>
          </a>
          
          <ul class="dropdown-menu" [class.show]="userMenuOpen">
            <li>
              <a class="page-link" routerLink="/user/password">
                <i class="pe pe-7s-key pe-rotate-90 pe-fw pe-2x pe-va"></i>
                Change Password
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a class="page-link" href="#" (click)="onLogout($event)">
                <i class="pe pe-7s-download pe-rotate-270 pe-fw pe-2x pe-va"></i>
                Logout
              </a>
            </li>
          </ul>
        </li>
      </ul>
      
      <!-- Progress Bar -->
      <div class="progress" id="progress1" [style.visibility]="showProgress ? 'visible' : 'hidden'">
        <div class="loader">
          <div class="bar"></div>
        </div>
      </div>
    </div>
  `,
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent {
  @Input() user: User | null = null;
  @Output() menuToggle = new EventEmitter<void>();
  @Output() logout = new EventEmitter<void>();

  userMenuOpen = false;
  showProgress = false;

  onMenuToggle(): void {
    this.menuToggle.emit();
  }

  toggleUserMenu(event: Event): void {
    event.preventDefault();
    this.userMenuOpen = !this.userMenuOpen;
  }

  onLogout(event: Event): void {
    event.preventDefault();
    this.userMenuOpen = false;
    this.logout.emit();
  }

  // Method to show/hide progress bar (can be called from parent)
  setProgress(show: boolean): void {
    this.showProgress = show;
  }
}
