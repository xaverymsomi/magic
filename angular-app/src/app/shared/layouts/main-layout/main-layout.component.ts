import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { HeaderComponent } from '../header/header.component';
import { FooterComponent } from '../footer/footer.component';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    SidebarComponent,
    HeaderComponent,
    FooterComponent
  ],
  templateUrl: './main-layout.component.html',
  styleUrls: ['./main-layout.component.scss']
})
export class MainLayoutComponent implements OnInit {
  sidebarCollapsed = false;
  showOverlay = false;

  constructor() {}

  ngOnInit(): void {
    // Initialize layout state
    this.loadLayoutPreferences();
  }

  toggleSidebar(): void {
    this.sidebarCollapsed = !this.sidebarCollapsed;
    this.saveLayoutPreferences();
  }

  private loadLayoutPreferences(): void {
    const collapsed = localStorage.getItem('sidebarCollapsed');
    if (collapsed !== null) {
      this.sidebarCollapsed = JSON.parse(collapsed);
    }
  }

  private saveLayoutPreferences(): void {
    localStorage.setItem('sidebarCollapsed', JSON.stringify(this.sidebarCollapsed));
  }

  showLoadingOverlay(): void {
    this.showOverlay = true;
  }

  hideLoadingOverlay(): void {
    this.showOverlay = false;
  }
}
