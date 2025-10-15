import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable()
export class CorsInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Only modify requests in development
    if (!environment.production) {
      // Check if the request is going to an external URL (not through proxy)
      if (req.url.startsWith('http://mabrex.rahisi')) {
        // Convert to relative URL so proxy can handle it
        const relativeUrl = req.url.replace('http://mabrex.rahisi', '');
        const modifiedReq = req.clone({
          url: relativeUrl,
          setHeaders: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });
        return next.handle(modifiedReq);
      }
    }
    
    return next.handle(req);
  }
}
