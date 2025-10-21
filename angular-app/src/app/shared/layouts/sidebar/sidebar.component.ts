import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavigationComponent } from '../../components/navigation/navigation.component';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, NavigationComponent],
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {
  @Input() isCollapsed = false;
  @Output() toggleSidebar = new EventEmitter<void>();

  constructor() {}

  ngOnInit(): void {
    // Navigation logic is now handled by NavigationComponent
  }

  onToggleSidebar(): void {
    this.toggleSidebar.emit();
  }
}
