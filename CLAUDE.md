# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CitaPro is a full-stack medical appointment management system for "Clínica La Guerra Mendez". It uses Vue.js 3 for the frontend and PHP with MySQL for the backend, implementing role-based access control for patients, doctors, insurance companies, coordinators, and administrators.

## Development Commands

```bash
# Install dependencies
npm install        # Frontend dependencies
composer install   # PHP dependencies

# Development
npm run dev       # Start Vite dev server (port 5173)

# Build for production
npm run build     # Build frontend assets to assets/dist

# Preview production build
npm run preview
```

## Architecture Overview

### Frontend Architecture
- **Framework**: Vue.js 3 with Composition API
- **Build Tool**: Vite with path aliases configured (@, @components, @store)
- **Routing**: Vue Router 4 with role-based navigation guards
- **State Management**: Pinia stores for global state
- **UI Library**: PrimeVue 3 components + Bootstrap 5
- **HTTP Client**: Axios for API communication

### Backend Architecture
- **API Structure**: RESTful endpoints in `/api` directory
- **Authentication**: JWT tokens (custom implementation)
- **Database**: MySQL/MariaDB with PDO
- **File Organization**: Endpoints grouped by entity (citas, pacientes, doctores, etc.)
- **Standard CRUD pattern**: crear.php, listar.php, actualizar.php, eliminar.php

### User Roles & Access Levels
1. **admin** - Full system access
2. **coordinador** - Appointment coordination, patient management
3. **doctor** - Personal schedule, patient appointments
4. **aseguradora** - Insurance company portal
5. **paciente** - Book appointments, view history
6. **vertice** - Special administrative features

## Key API Patterns

### Authentication
- All API endpoints require JWT token in Authorization header: `Bearer <token>`
- Login endpoint: `/api/auth/login.php`
- Token refresh: `/api/auth/refresh.php`

### API Response Format
```json
{
  "success": true/false,
  "message": "Response message",
  "data": {} // Response data
}
```

### Common API Operations
- **CRUD endpoints**: `crear.php`, `listar.php`, `actualizar.php`, `eliminar.php`
- **Specialized endpoints**: `asignar.php`, `confirmar.php`, `cancelar.php` (for appointments)
- **File uploads**: Profile photos handled via FormData

## Database Schema Key Tables

### Core Tables
- **usuarios**: System users with roles and profile photos (foto_perfil)
- **pacientes**: Patient profiles with insurance holder info (es_titular, titular_id, tipo, parentesco)
- **doctores**: Doctor profiles with specialties
- **citas**: Appointments with states and insurance policy link (paciente_seguro_id)
- **aseguradoras**: Insurance companies
- **consultorios**: Medical offices/consultation rooms
- **especialidades**: Medical specialties
- **subespecialidades**: Subspecialties linked to main specialties

### Insurance Management
- **titulares**: Insurance policy holders with personal data and affiliation numbers
- **pacientes_seguros**: Patient insurance policies (multiple per patient, coverage types, expiration tracking)
- **pacientes_seguros_historial**: Audit trail for insurance changes with JSON data
- **v_pacientes_seguros_completo**: View joining patient insurance data with status calculations

### Schedule Management
- **horarios_doctores**: Doctor schedules with block types and duration
- **tipos_bloque_horario**: Schedule block types with colors for UI
- **citas_horarios**: Junction table linking appointments to time blocks
- **logs_horarios**: Schedule change audit trail with JSON data

### Notification System
- **notificaciones**: Standard notifications
- **temp_notificaciones**: Enhanced notification queue (WhatsApp, email, SMS) with retry logic

### Temporary Processing Tables
- **temp_solicitudes**: Insurance company appointment requests
- **temp_asignaciones**: Temporary appointment assignments
- **temp_turnos_disponibles**: Available appointment slots
- **temp_doctores_activos**: Active doctor status
- **temp_horarios_doctores**: Simplified temporary schedules
- **temp_horarios_backup**: Schedule backups

### Audit & Logging
- **logs_auditoria**: General audit trail for all actions
- **logs_horarios**: Specific schedule change tracking

## Component Organization

Frontend components are organized by user role:
- `/components/Admin/` - Admin dashboard, user management
- `/components/Doctor/` - Doctor schedule, appointments
- `/components/Paciente/` - Patient portal components
- `/components/Coordinador/` - Appointment coordination
- `/components/Shared/` - Reusable components across roles
- `/components/Auth/` - Login, registration, password reset

## State Management (Pinia)

Key stores:
- **authStore**: User authentication state and methods
- **userStore**: Current user profile
- **appointmentStore**: Appointment management
- **patientStore**: Patient data management

## Development Guidelines

### API Development
- Always validate JWT tokens before processing requests
- Use prepared statements with PDO for database queries
- Return consistent JSON responses with success/error status
- Log important actions to `logs_auditoria` table

### Frontend Development
- Components should follow Vue 3 Composition API patterns
- Use PrimeVue components for UI consistency
- Handle API errors with toast notifications
- Implement proper loading states for async operations
- Route guards should check user roles before allowing access

### Common Patterns
- Date/time handling: Use proper timezone management (stored as UTC)
- File uploads: Validate file types and sizes on both frontend and backend
- Form validation: Client-side with backend verification
- Pagination: Implemented on list endpoints with limit/offset

## Testing & Debugging

### Frontend Debugging
- Vue DevTools for component inspection
- Network tab for API request/response monitoring
- Console logs are acceptable during development

### Backend Debugging
- Check PHP error logs for server errors
- Use `error_log()` for debugging PHP endpoints
- Verify JWT tokens with online JWT debugger

## Security Considerations

- Never commit credentials or API keys
- JWT secret is stored in `/api/config/config.php`
- Database credentials in same config file
- CORS headers configured for API access
- Password hashing uses PHP's `password_hash()` with default algorithm

## Notification System

The system supports multiple notification channels:
- **Email**: PHPMailer configuration in `/api/notificaciones/email.php`
- **SMS**: Twilio integration in `/api/notificaciones/sms.php`
- **WhatsApp**: Integration in `/api/notificaciones/whatsapp.php`

## Common Development Tasks

### Adding a New API Endpoint
1. Create PHP file in appropriate `/api/[entity]/` directory
2. Include JWT validation from `/api/config/auth.php`
3. Follow existing CRUD pattern for consistency
4. Add appropriate CORS headers
5. Return JSON response with success/error status

### Adding a New Vue Component
1. Create component in appropriate role directory
2. Register route in `/router/index.js` with role guard
3. Import and use PrimeVue components as needed
4. Connect to API using Axios with auth token
5. Handle loading states and errors appropriately

### Database Migrations
- Schema changes should be added to `structure2306.sql` (latest schema)
- Consider existing data when modifying tables
- Update relevant PHP endpoints after schema changes

## Key Database Relationships

### Insurance Coverage Flow
- `titulares` (policy holders) → `pacientes` (via titular_id for dependents)
- `pacientes` → `pacientes_seguros` (multiple insurance policies per patient)
- `pacientes_seguros` → `aseguradoras` (insurance companies)
- `citas` → `pacientes_seguros` (specific insurance used for appointment)

### Appointment Request Flow (Insurance Companies)
1. `temp_solicitudes` - Insurance company creates appointment request
2. `temp_asignaciones` - System assigns to available slots
3. `temp_turnos_disponibles` - Available time slots checked
4. `temp_notificaciones` - Notifications sent to relevant parties

### Schedule Management
- `horarios_doctores` → `tipos_bloque_horario` (colored block types)
- `citas` → `citas_horarios` → `horarios_doctores` (appointment time tracking)
- Changes tracked in `logs_horarios` with JSON before/after data

### Important Field Notes
- **pacientes.tipo**: 'asegurado' or 'particular' (insured vs private pay)
- **pacientes_seguros.tipo_cobertura**: 'principal', 'secundario', 'complementario'
- **pacientes_seguros.es_beneficiario**: Boolean for dependent coverage
- **temp_notificaciones**: Supports retry_count and last_retry for failed notifications