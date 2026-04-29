// Build the OpenAPI document dynamically so the docs always point at the current local server URL.
module.exports = function buildDeveloperApiSpec(baseUrl) {
  const errorResponse = {
    description: "Request failed.",
    content: {
      "application/json": {
        schema: {
          $ref: "#/components/schemas/ErrorResponse",
        },
      },
    },
  };

  const bearerSecurity = [{ BearerAuth: [] }];

  return {
    openapi: "3.0.3",
    info: {
      title: "Alumni Platform CW2 Developer API",
      version: "2.0.0",
      description:
        "Scoped bearer-token API for the CW2 staff analytics dashboard and mobile AR alumnus-of-day client.",
    },
    servers: [
      {
        url: baseUrl,
        description: "Local coursework server",
      },
    ],
    tags: [
      {
        name: "AnalyticsDashboard",
        description:
          "Endpoints for the localhost:3001 staff analytics dashboard. Tokens need read:alumni and/or read:analytics.",
      },
      {
        name: "MobileAR",
        description:
          "Endpoints for mobile AR clients. Tokens need read:alumni_of_day and cannot access analytics endpoints.",
      },
    ],
    components: {
      securitySchemes: {
        BearerAuth: {
          type: "http",
          scheme: "bearer",
          bearerFormat: "API Token",
          description:
            "Create a bearer token in the developer portal. Analytics Dashboard tokens use read:alumni/read:analytics. Mobile AR App tokens use read:alumni_of_day.",
        },
      },
      schemas: {
        AlumniProfileSummary: {
          type: "object",
          properties: {
            userId: { type: "integer", example: 12 },
            fullName: { type: "string", example: "Jane Doe" },
            email: { type: "string", example: "jane.doe@iit.ac.lk" },
            isVerified: { type: "boolean", example: true },
            contactNumber: { type: "string", example: "+94 77 123 4567" },
            location: { type: "string", example: "Colombo" },
            programme: { type: "string", example: "BSc Computer Science" },
            graduationDate: { type: "string", example: "2026-07-15" },
            industrySector: { type: "string", example: "Software Engineering" },
            professionalHeadline: { type: "string", example: "Senior Software Engineer" },
            linkedinUrl: { type: "string", example: "https://www.linkedin.com/in/jane-doe" },
            updatedAt: { type: "string", format: "date-time" },
          },
        },
        AlumniDirectoryResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: true },
            filters: {
              type: "object",
              properties: {
                programme: { type: "string", example: "BSc Computer Science" },
                graduationYear: { type: "string", example: "2026" },
                graduationDate: { type: "string", example: "2026-07-15" },
                industrySector: { type: "string", example: "Software Engineering" },
              },
            },
            totalMatches: { type: "integer", example: 8 },
            data: {
              type: "array",
              items: {
                $ref: "#/components/schemas/AlumniProfileSummary",
              },
            },
          },
        },
        AnalyticsSummaryResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: true },
            data: {
              type: "object",
              properties: {
                summary: {
                  type: "object",
                  properties: {
                    registeredAlumni: { type: "integer", example: 40 },
                    verifiedAlumni: { type: "integer", example: 36 },
                    completedProfiles: { type: "integer", example: 31 },
                    totalBids: { type: "integer", example: 18 },
                    featuredWinners: { type: "integer", example: 7 },
                    totalApiRequests: { type: "integer", example: 120 },
                    activeTokens: { type: "integer", example: 3 },
                  },
                },
                graphs: {
                  type: "object",
                  properties: {
                    byProgramme: {
                      type: "array",
                      items: { $ref: "#/components/schemas/ChartPoint" },
                    },
                    byGraduationYear: {
                      type: "array",
                      items: { $ref: "#/components/schemas/ChartPoint" },
                    },
                    byIndustrySector: {
                      type: "array",
                      items: { $ref: "#/components/schemas/ChartPoint" },
                    },
                    byBidStatus: {
                      type: "array",
                      items: { $ref: "#/components/schemas/ChartPoint" },
                    },
                    byApiEndpoint: {
                      type: "array",
                      items: { $ref: "#/components/schemas/ChartPoint" },
                    },
                  },
                },
              },
            },
          },
        },
        ChartPoint: {
          type: "object",
          properties: {
            label: { type: "string", example: "BSc Computer Science" },
            value: { type: "integer", example: 12 },
          },
        },
        FeaturedAlumnusResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: true },
            data: {
              type: "object",
              properties: {
                featuredDate: { type: "string", example: "2026-04-29" },
                featureMonthKey: { type: "string", example: "2026-04" },
                featureMonthLabel: { type: "string", example: "April 2026" },
                alumni: {
                  type: "object",
                  properties: {
                    fullName: { type: "string", example: "Jane Doe" },
                    email: { type: "string", example: "jane.doe@iit.ac.lk" },
                    contactNumber: { type: "string", example: "+94 77 123 4567" },
                    programme: { type: "string", example: "BSc Computer Science" },
                    graduationDate: { type: "string", example: "2026-07-15" },
                    industrySector: { type: "string", example: "Software Engineering" },
                    professionalHeadline: { type: "string", example: "Senior Software Engineer" },
                    biography: {
                      type: "string",
                      example: "Alumna working in cloud engineering and developer platforms.",
                    },
                    linkedinUrl: {
                      type: "string",
                      example: "https://www.linkedin.com/in/jane-doe",
                    },
                    location: { type: "string", example: "Colombo" },
                    profileImageUrl: {
                      type: "string",
                      example: "/uploads/profiles/example.png",
                    },
                    selectedAt: {
                      type: "string",
                      format: "date-time",
                      example: "2026-04-29T00:00:00.000Z",
                    },
                  },
                },
              },
            },
          },
        },
        ErrorResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: false },
            error: { type: "string", example: "Missing bearer token." },
          },
        },
      },
    },
    paths: {
      "/api/v1/alumni": {
        get: {
          tags: ["AnalyticsDashboard"],
          summary: "Read filtered alumni directory records",
          description:
            "Requires read:alumni. Supports programme, graduationYear, graduationDate, and industrySector query filters.",
          security: bearerSecurity,
          parameters: [
            {
              name: "programme",
              in: "query",
              schema: { type: "string" },
            },
            {
              name: "graduationYear",
              in: "query",
              schema: { type: "string" },
            },
            {
              name: "graduationDate",
              in: "query",
              schema: { type: "string" },
            },
            {
              name: "industrySector",
              in: "query",
              schema: { type: "string" },
            },
          ],
          responses: {
            "200": {
              description: "Alumni directory returned successfully.",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/AlumniDirectoryResponse" },
                },
              },
            },
            "401": errorResponse,
            "403": errorResponse,
          },
        },
      },
      "/api/v1/analytics/summary": {
        get: {
          tags: ["AnalyticsDashboard"],
          summary: "Read CW2 dashboard analytics",
          description:
            "Requires read:analytics. Mobile AR App tokens cannot access this endpoint.",
          security: bearerSecurity,
          responses: {
            "200": {
              description: "Analytics summary returned successfully.",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/AnalyticsSummaryResponse" },
                },
              },
            },
            "401": errorResponse,
            "403": errorResponse,
          },
        },
      },
      "/api/v1/alumni-of-day": {
        get: {
          tags: ["MobileAR"],
          summary: "Read the alumnus-of-day feed",
          description:
            "Requires read:alumni_of_day. Analytics Dashboard tokens cannot access this endpoint unless separately issued with that scope.",
          security: bearerSecurity,
          responses: {
            "200": {
              description: "Alumnus of the day returned successfully.",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/FeaturedAlumnusResponse" },
                },
              },
            },
            "401": errorResponse,
            "403": errorResponse,
            "404": errorResponse,
          },
        },
      },
      "/api/v1/featured-alumnus/today": {
        get: {
          tags: ["MobileAR"],
          summary: "Compatibility alias for alumnus-of-day",
          description:
            "Requires read:alumni_of_day. This path is kept for earlier CW1/CW2 clients.",
          security: bearerSecurity,
          responses: {
            "200": {
              description: "Featured alumnus returned successfully.",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/FeaturedAlumnusResponse" },
                },
              },
            },
            "401": errorResponse,
            "403": errorResponse,
            "404": errorResponse,
          },
        },
      },
    },
  };
};
