import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl = environment.production ? environment.apiUrl : '';

  constructor(private http: HttpClient) {}

  // Preserve existing PHP endpoint calling patterns
  get(endpoint: string, params?: any): Observable<any> {
    let httpParams = new HttpParams();
    if (params) {
      Object.keys(params).forEach(key => {
        httpParams = httpParams.set(key, params[key]);
      });
    }
    return this.http.get(`${this.baseUrl}${endpoint}`, { params: httpParams });
  }

  post(endpoint: string, data: any): Observable<any> {
    // Preserve existing form-urlencoded format for PHP compatibility
    const headers = new HttpHeaders({
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
    });
    
    // Convert object to form data format
    const formData = this.objectToFormData(data);
    return this.http.post(`${this.baseUrl}${endpoint}`, formData, { headers });
  }

  postWithFiles(endpoint: string, data: any, files: { [key: string]: File }): Observable<any> {
    const formData = new FormData();
    
    // Add regular form fields
    Object.keys(data).forEach(key => {
      if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key]);
      }
    });
    
    // Add files
    Object.keys(files).forEach(key => {
      formData.append(key, files[key]);
    });
    
    return this.http.post(`${this.baseUrl}${endpoint}`, formData);
  }

  // Load form templates (preserving showForm logic)
  loadFormTemplate(url: string, action: string, params: any[] = []): Observable<any> {
    let formUrl = `/${url.charAt(0).toUpperCase() + url.slice(1)}/${action.toLowerCase()}`;
    if (params.length > 0) {
      formUrl += '/' + params.join('/');
    }
    return this.get(formUrl + ' #page-content');
  }

  // Helper method to convert object to form-urlencoded format
  private objectToFormData(obj: any): string {
    const formData: string[] = [];
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        const value = obj[key];
        if (value !== null && value !== undefined) {
          formData.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
        }
      }
    }
    return formData.join('&');
  }
}