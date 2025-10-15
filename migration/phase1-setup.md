# Phase 1: Infrastructure Setup - Detailed Implementation

## Step 1: Angular CLI Installation and Project Setup

### 1.1 Install Angular CLI
```bash
npm install -g @angular/cli@17
ng new magic-angular-app --routing --style=scss --package-manager=npm
cd magic-angular-app
```

### 1.2 Install Required Dependencies
```bash
# Angular Material
ng add @angular/material

# AngularJS Upgrade Support
npm install @angular/upgrade @angular/upgrade-static

# Additional Dependencies
npm install @angular/animations
npm install @angular/cdk
npm install rxjs
npm install chart.js ng2-charts
npm install moment
npm install bootstrap@5.3.0

# Development Dependencies
npm install --save-dev @types/angular
npm install --save-dev webpack-bundle-analyzer
```

### 1.3 Project Configuration

#### angular.json Configuration
```json
{
  "projects": {
    "magic-angular-app": {
      "architect": {
        "build": {
          "options": {
            "assets": [
              "src/favicon.ico",
              "src/assets",
              {
                "glob": "**/*",
                "input": "../public/assets/images",
                "output": "/assets/images"
              }
            ],
            "styles": [
              "@angular/material/prebuilt-themes/indigo-pink.css",
              "node_modules/bootstrap/dist/css/bootstrap.min.css",
              "src/styles.scss"
            ],
            "scripts": [
              "node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
            ]
          }
        }
      }
    }
  }
}
```

## Step 2: Hybrid Environment Setup

### 2.1 Create Hybrid Bootstrap Module
```typescript
// src/app/hybrid/hybrid.module.ts
import { NgModule } from '@angular/core';
import { UpgradeModule } from '@angular/upgrade/static';
import { BrowserModule } from '@angular/platform-browser';

@NgModule({
  imports: [
    BrowserModule,
    UpgradeModule
  ]
})
export class HybridModule {
  constructor(private upgrade: UpgradeModule) {}

  ngDoBootstrap() {
    this.upgrade.bootstrap(document.body, ['app'], { strictDi: true });
  }
}
```

### 2.2 Create Service Bridge
```typescript
// src/app/core/services/api.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl = ''; // Will use existing PHP endpoints

  constructor(private http: HttpClient) {}

  // Preserve existing API call patterns
  get(endpoint: string, params?: any): Observable<any> {
    return this.http.get(`${this.baseUrl}${endpoint}`, { params });
  }

  post(endpoint: string, data: any): Observable<any> {
    const headers = new HttpHeaders({
      'Content-Type': 'application/x-www-form-urlencoded'
    });
    return this.http.post(`${this.baseUrl}${endpoint}`, data, { headers });
  }
}
```

## Step 3: TypeScript Interfaces for Existing Data Models

### 3.1 Form Data Models
```typescript
// src/app/shared/models/form.models.ts
export interface FormData {
  id?: number;
  [key: string]: any;
}

export interface DropdownOption {
  id: string | number;
  name: string;
  value?: any;
}

export interface FormConfig {
  url: string;
  actionname: string;
  dropdowns: { [key: string]: DropdownOption[] };
  form: FormData;
  disabled?: string[];
}

export interface ModalConfig {
  template: string;
  windowClass: string;
  scope: any;
}
```

### 3.2 User and Authentication Models
```typescript
// src/app/shared/models/auth.models.ts
export interface User {
  id: number;
  username: string;
  email: string;
  permissions: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  module: string;
}

export interface LoginRequest {
  username: string;
  password: string;
}

export interface AuthResponse {
  status: boolean;
  code: number;
  message: string;
  user?: User;
  token?: string;
}
```

## Step 4: Core Services Migration

### 4.1 Authentication Service
```typescript
// src/app/core/services/auth.service.ts
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { ApiService } from './api.service';
import { User, LoginRequest, AuthResponse } from '../../shared/models/auth.models';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private apiService: ApiService) {}

  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.apiService.post('/Login/authenticate', credentials);
  }

  logout(): void {
    // Preserve existing logout logic
    this.currentUserSubject.next(null);
    // Call existing PHP logout endpoint
    this.apiService.post('/Logout', {}).subscribe();
  }

  hasPermission(permission: string): boolean {
    const user = this.currentUserSubject.value;
    return user?.permissions.some(p => p.name === permission) || false;
  }
}
```

### 4.2 Form Service (Preserving Complex Logic)
```typescript
// src/app/core/services/form.service.ts
import { Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { FormModalComponent } from '../../shared/components/form-modal/form-modal.component';

@Injectable({
  providedIn: 'root'
})
export class FormService {
  constructor(
    private dialog: MatDialog,
    private apiService: ApiService
  ) {}

  // Preserve showForm logic from angular-create.js
  showForm(url: string, action: string, params: any[] = []): Observable<any> {
    const formUrl = this.buildFormUrl(url, action, params);
    
    return new Observable(observer => {
      // Load form data (preserving existing PHP endpoint calls)
      this.apiService.get(formUrl).subscribe(response => {
        const dialogRef = this.dialog.open(FormModalComponent, {
          width: '80%',
          maxWidth: '1200px',
          data: {
            template: response.template,
            dropdowns: response.dropdowns,
            form: response.form,
            url: url,
            action: action
          }
        });

        dialogRef.afterClosed().subscribe(result => {
          observer.next(result);
          observer.complete();
        });
      });
    });
  }

  private buildFormUrl(url: string, action: string, params: any[]): string {
    let formUrl = `/${url.charAt(0).toUpperCase() + url.slice(1)}/${action.toLowerCase()}`;
    if (params.length > 0) {
      formUrl += '/' + params.join('/');
    }
    return formUrl;
  }

  // Preserve saveForm logic
  saveForm(url: string, formData: any, action: string = ''): Observable<any> {
    const postUrl = action ? 
      `/${url.charAt(0).toUpperCase() + url.slice(1)}/${action}/` :
      `/${url.charAt(0).toUpperCase() + url.slice(1)}/save/`;
    
    return this.apiService.post(postUrl, formData);
  }
}
```

## Step 5: Routing Configuration

### 5.1 Angular Router Setup
```typescript
// src/app/app-routing.module.ts
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';

const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./features/auth/auth.module').then(m => m.AuthModule)
  },
  {
    path: 'dashboard',
    loadChildren: () => import('./features/dashboard/dashboard.module').then(m => m.DashboardModule),
    canActivate: [AuthGuard]
  },
  {
    path: '',
    redirectTo: '/dashboard',
    pathMatch: 'full'
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, {
    enableTracing: false, // Set to true for debugging
    useHash: false // Use HTML5 routing
  })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
```

## Step 6: Build Configuration

### 6.1 Environment Configuration
```typescript
// src/environments/environment.ts
export const environment = {
  production: false,
  apiUrl: '', // Will use relative URLs to existing PHP backend
  appUrl: 'http://localhost:4200'
};

// src/environments/environment.prod.ts
export const environment = {
  production: true,
  apiUrl: '', // Production PHP backend URL
  appUrl: '' // Production Angular app URL
};
```

### 6.2 Proxy Configuration for Development
```json
// proxy.conf.json
{
  "/api/*": {
    "target": "http://localhost:8089",
    "secure": false,
    "changeOrigin": true,
    "logLevel": "debug"
  },
  "/*.php": {
    "target": "http://localhost:8089",
    "secure": false,
    "changeOrigin": true
  }
}
```

## Step 7: Testing Setup

### 7.1 Unit Testing Configuration
```typescript
// src/app/core/services/auth.service.spec.ts
import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService]
    });
    service = TestBed.inject(AuthService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  // Add tests for existing business logic
});
```

## Deliverables for Phase 1
1. ✅ Angular 17 project setup
2. ✅ Hybrid environment configuration
3. ✅ Core service architecture
4. ✅ TypeScript interfaces for existing data models
5. ✅ Development proxy configuration
6. ✅ Testing framework setup
7. ✅ Build and deployment configuration

## Next Phase Preview
Phase 2 will focus on migrating the authentication system and creating the first Angular components while maintaining full compatibility with the existing AngularJS application.